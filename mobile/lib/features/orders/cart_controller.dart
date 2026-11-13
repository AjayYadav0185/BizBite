import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../core/utils/india.dart';
import '../menu/data/models/food_item_model.dart';
import '../ops/data/models/console_models.dart';
import 'data/models/cart_line.dart';
import 'data/models/order_models.dart';

/// A parked bill (Hold / Resume), persisted on the device.
class HeldBill {
  const HeldBill({
    required this.id,
    required this.label,
    required this.heldAt,
    required this.total,
    required this.count,
    required this.state,
  });

  final String id;
  final String label;
  final DateTime heldAt;
  final double total;
  final int count;
  final Map<String, dynamic> state;

  factory HeldBill.fromJson(Map<String, dynamic> j) => HeldBill(
        id: '${j['id']}',
        label: '${j['label']}',
        heldAt: DateTime.tryParse('${j['held_at']}') ?? DateTime.now(),
        total: (j['total'] as num?)?.toDouble() ?? 0,
        count: (j['count'] as num?)?.toInt() ?? 0,
        state: Map<String, dynamic>.from(j['state'] as Map? ?? const {}),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'label': label,
        'held_at': heldAt.toIso8601String(),
        'total': total,
        'count': count,
        'state': state,
      };
}

/// The cashier's in-progress bill.
///
/// Mirrors the Livewire `BillingDashboard`: keyed by food_item_id, live
/// totals, coupon preview with the SAME rules as the server
/// (`Campaign::discountFor`), nearest-rupee round-off exactly like
/// `OrderService`, counter guards (credit needs a customer, dine-in needs a
/// table, valid Indian mobile) and Hold / Resume of parked bills.
class CartController with ChangeNotifier implements Listenable {
  // ignore: prefer_initializing_formals
  CartController({SharedPreferences? prefs}) : _prefs = prefs {
    _loadHeld();
  }

  static const String heldKey = 'bizbite.pos.held_bills.v1';
  static const int maxHeld = 20;

  final SharedPreferences? _prefs;
  final Map<int, CartLine> _lines = {};

  PaymentMode paymentMode = PaymentMode.cash;
  OrderType orderType = OrderType.takeaway;
  double discountAmount = 0.0;
  String customerName = '';
  String customerPhone = '';
  String upiRef = '';
  String tableNumber = '';
  String campaignCode = '';
  String notes = '';
  String deliveryAddress = '';
  double tenderedAmount = 0.0;

  /// Split-tender legs: [{mode, amount}].
  List<Map<String, String>> splitLegs = [];

  /// Live coupons for the preview (from the console cache; works offline).
  List<CampaignModel> campaigns = const [];

  List<HeldBill> held = [];

  /// Cart lines in insertion order.
  List<CartLine> get lines => _lines.values.toList();

  bool get isEmpty => _lines.isEmpty;

  int get totalQuantity =>
      _lines.values.fold(0, (sum, line) => sum + line.quantity);

  int quantityOf(int foodItemId) => _lines[foodItemId]?.quantity ?? 0;

  /// Sum of line totals before any discount.
  double get subtotal => India.paise(
      _lines.values.fold(0.0, (sum, line) => sum + line.lineTotal));

  /// Manual discount, clamped to the subtotal.
  double get effectiveDiscount => discountAmount.clamp(0, subtotal);

  /// Coupon discount preview (0 when the code is unknown / not live / below
  /// the minimum order), capped at what's left after the manual discount.
  double get campaignDiscount {
    final code = campaignCode.trim().toUpperCase();
    if (code.isEmpty || isEmpty) return 0;
    final match = campaigns.where((c) => c.code.toUpperCase() == code);
    if (match.isEmpty) return 0;
    final d = match.first.discountFor(subtotal);
    final room = India.paise(subtotal - effectiveDiscount);
    return d > room ? room : d;
  }

  /// null = no code typed, true = applied, false = not valid for this bill.
  bool? get campaignValid {
    if (campaignCode.trim().isEmpty || isEmpty) return null;
    return campaignDiscount > 0;
  }

  /// Total discount (manual + coupon) the server will apply.
  double get totalDiscount => India.paise(effectiveDiscount + campaignDiscount);

  /// Before round-off.
  double get netAmount =>
      India.paise((subtotal - totalDiscount).clamp(0, double.infinity));

  /// Amount to collect — nearest rupee, identical to OrderService.
  double get payable => India.roundRupee(netAmount);

  double get roundOff => India.paise(payable - netAmount);

  double get changeDue =>
      tenderedAmount > payable ? India.paise(tenderedAmount - payable) : 0;

  double get splitTotal => India.paise(splitLegs.fold(
      0.0, (sum, leg) => sum + (double.tryParse(leg['amount'] ?? '') ?? 0)));

  double get splitRemaining => India.paise(payable - splitTotal);

  /// Kitchen note sent with the bill: the bill note plus per-item notes
  /// ("Paneer Tikka: less spicy"), capped at the API's 200 characters.
  String get combinedNotes {
    final parts = <String>[
      if (notes.trim().isNotEmpty) notes.trim(),
      for (final line in _lines.values)
        if (line.note.isNotEmpty) '${line.foodItem.name}: ${line.note}',
    ];
    final joined = parts.join('; ');
    return joined.length > 200 ? joined.substring(0, 200) : joined;
  }

  /// Counter guards — returns a cashier-facing message, or null when OK.
  String? validate() {
    if (isEmpty) return 'Cart is empty — add items before settling.';
    if (customerPhone.trim().isNotEmpty &&
        India.normaliseMobile(customerPhone) == null) {
      return 'Customer mobile must be a valid 10-digit Indian number.';
    }
    if (paymentMode == PaymentMode.credit &&
        customerName.trim().isEmpty &&
        customerPhone.trim().isEmpty) {
      return 'Credit (pay later) bills need the customer name or mobile.';
    }
    if (orderType == OrderType.dineIn && tableNumber.trim().isEmpty) {
      return 'Enter the table number for a dine-in bill.';
    }
    if (paymentMode == PaymentMode.split && splitRemaining != 0) {
      return splitRemaining > 0
          ? 'Split legs are short by ₹${India.number(splitRemaining)}.'
          : 'Split legs exceed the bill by ₹${India.number(-splitRemaining)}.';
    }
    return null;
  }

  // ---------------------------------------------------------------------------
  // Mutations
  // ---------------------------------------------------------------------------

  void add(FoodItemModel item, {int quantity = 1}) {
    final existing = _lines[item.id];
    _lines[item.id] = CartLine(
      foodItem: item,
      quantity: (existing?.quantity ?? 0) + quantity,
      note: existing?.note ?? '',
    );
    notifyListeners();
  }

  void setQuantity(int foodItemId, int quantity) {
    if (quantity <= 0) {
      remove(foodItemId);
      return;
    }
    final existing = _lines[foodItemId];
    if (existing == null) return;
    _lines[foodItemId] = existing.copyWith(quantity: quantity);
    notifyListeners();
  }

  void setNote(int foodItemId, String note) {
    final existing = _lines[foodItemId];
    if (existing == null) return;
    final trimmed = note.trim();
    if (existing.note == trimmed) return;
    _lines[foodItemId] = existing.copyWith(note: trimmed);
    notifyListeners();
  }

  void remove(int foodItemId) {
    if (_lines.remove(foodItemId) != null) notifyListeners();
  }

  void _resetBill() {
    _lines.clear();
    discountAmount = 0.0;
    customerName = '';
    customerPhone = '';
    upiRef = '';
    tableNumber = '';
    campaignCode = '';
    notes = '';
    deliveryAddress = '';
    tenderedAmount = 0.0;
    splitLegs = [];
    paymentMode = PaymentMode.cash;
    orderType = OrderType.takeaway;
  }

  /// Clear the whole cart ("new customer").
  void clear() {
    _resetBill();
    notifyListeners();
  }

  void setPaymentMode(PaymentMode mode) {
    if (paymentMode == mode) return;
    paymentMode = mode;
    notifyListeners();
  }

  void setOrderType(OrderType type) {
    if (orderType == type) return;
    orderType = type;
    notifyListeners();
  }

  void setDiscountAmount(double amount) {
    final clamped = amount.clamp(0, subtotal);
    if (discountAmount == clamped) return;
    discountAmount = clamped.toDouble();
    notifyListeners();
  }

  void setCustomerName(String v) => _set(() => customerName = v);
  void setCustomerPhone(String v) => _set(() => customerPhone = v);
  void setUpiRef(String v) => _set(() => upiRef = v);
  void setTableNumber(String v) => _set(() => tableNumber = v);
  void setCampaignCode(String v) => _set(() => campaignCode = v.toUpperCase());
  void setNotes(String v) => _set(() => notes = v);
  void setDeliveryAddress(String v) => _set(() => deliveryAddress = v);
  void setTendered(double v) => _set(() => tenderedAmount = v < 0 ? 0 : v);

  void setCampaigns(List<CampaignModel> list) => _set(() => campaigns = list);

  void addSplitLeg(PaymentMode mode, double amount) {
    if (mode == PaymentMode.split || amount <= 0) return;
    splitLegs = [
      ...splitLegs,
      {'mode': mode.wireValue, 'amount': amount.toStringAsFixed(2)},
    ];
    notifyListeners();
  }

  void removeSplitLeg(int index) {
    if (index < 0 || index >= splitLegs.length) return;
    splitLegs = [...splitLegs]..removeAt(index);
    notifyListeners();
  }

  void _set(VoidCallback change) {
    change();
    notifyListeners();
  }

  // ---------------------------------------------------------------------------
  // Hold / resume
  // ---------------------------------------------------------------------------

  Map<String, dynamic> _snapshot() => {
        'lines': [
          for (final l in _lines.values)
            {'item': l.foodItem.toJson(), 'quantity': l.quantity, 'note': l.note},
        ],
        'order_type': orderType.wireValue,
        'payment_mode': paymentMode.wireValue,
        'discount': discountAmount,
        'customer_name': customerName,
        'customer_phone': customerPhone,
        'table_number': tableNumber,
        'campaign_code': campaignCode,
        'notes': notes,
        'delivery_address': deliveryAddress,
      };

  void _restore(Map<String, dynamic> s) {
    _resetBill();
    for (final raw in (s['lines'] as List? ?? const [])) {
      final m = Map<String, dynamic>.from(raw as Map);
      final item = FoodItemModel.fromJson(Map<String, dynamic>.from(m['item'] as Map));
      _lines[item.id] = CartLine(
        foodItem: item,
        quantity: (m['quantity'] as num?)?.toInt() ?? 1,
        note: '${m['note'] ?? ''}',
      );
    }
    orderType = orderTypeFromJson('${s['order_type']}');
    paymentMode = paymentModeFromJson('${s['payment_mode']}');
    discountAmount = (s['discount'] as num?)?.toDouble() ?? 0;
    customerName = '${s['customer_name'] ?? ''}';
    customerPhone = '${s['customer_phone'] ?? ''}';
    tableNumber = '${s['table_number'] ?? ''}';
    campaignCode = '${s['campaign_code'] ?? ''}';
    notes = '${s['notes'] ?? ''}';
    deliveryAddress = '${s['delivery_address'] ?? ''}';
  }

  /// Park the current bill. Returns an error message, or null on success.
  String? hold() {
    if (isEmpty) return 'Nothing to hold — the cart is empty.';
    if (held.length >= maxHeld) {
      return 'Too many bills on hold ($maxHeld). Settle or discard some first.';
    }
    final label = [
      if (tableNumber.trim().isNotEmpty) 'Table ${tableNumber.trim()}',
      if (customerName.trim().isNotEmpty) customerName.trim(),
    ].join(' · ');
    held = [
      ...held,
      HeldBill(
        id: DateTime.now().microsecondsSinceEpoch.toString(),
        label: label.isEmpty ? 'Bill ${held.length + 1}' : label,
        heldAt: DateTime.now(),
        total: payable,
        count: totalQuantity,
        state: _snapshot(),
      ),
    ];
    _resetBill();
    notifyListeners();
    _saveHeld();
    return null;
  }

  /// Bring a parked bill back; whatever is on screen is parked first.
  void resume(String id) {
    final index = held.indexWhere((b) => b.id == id);
    if (index < 0) return;
    final bill = held[index];
    if (!isEmpty) hold();
    held = held.where((b) => b.id != id).toList();
    _restore(bill.state);
    notifyListeners();
    _saveHeld();
  }

  void discardHeld(String id) {
    held = held.where((b) => b.id != id).toList();
    notifyListeners();
    _saveHeld();
  }

  Future<void> _loadHeld() async {
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      final raw = p.getString(heldKey);
      if (raw == null) return;
      held = (json.decode(raw) as List)
          .map((e) => HeldBill.fromJson(Map<String, dynamic>.from(e as Map)))
          // Parked bills older than a day are stale (yesterday's service).
          .where((b) => DateTime.now().difference(b.heldAt).inHours < 24)
          .toList();
      notifyListeners();
    } catch (_) {
      // Corrupt prefs never block billing.
    }
  }

  Future<void> _saveHeld() async {
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.setString(heldKey, json.encode(held.map((b) => b.toJson()).toList()));
    } catch (_) {}
  }
}

/// Readable inline clamp used above.
extension DoubleClamp on double {
  double clamp(double lower, double upper) =>
      this < lower ? lower : (this > upper ? upper : this);
}
