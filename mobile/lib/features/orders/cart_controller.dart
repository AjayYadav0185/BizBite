import '../menu/data/models/food_item_model.dart';
import 'data/models/cart_line.dart';
import 'data/models/order_models.dart';

/// The cashier's in-progress bill.
///
/// Mirrors the Livewire `BillingDashboard` cart semantics: keyed by
/// food_item_id, O(1) quantity bumps, live subtotal/payable computation and a
/// single equipment of payment mode + order type + optional discount/customer.
///
/// This is a pure value holder + mutator: every mutation calls
/// `notifyListeners()` so `ListenableBuilder`s that render the cart pane
/// rebuild automatically.
class CartController with ChangeNotifier {
  final Map<int, CartLine> _lines = {};

  PaymentMode paymentMode = PaymentMode.cash;
  OrderType orderType = OrderType.takeaway;
  double discountAmount = 0.0;
  String customerName = '';
  String customerPhone = '';
  String upiRef = '';

  /// Cart lines in insertion order.
  List<CartLine> get lines => _lines.values.toList();

  bool get isEmpty => _lines.isEmpty;

  /// Total number of plates/items on the bill ("x ITEMS").
  int get totalQuantity =>
      _lines.values.fold(0, (sum, line) => sum + line.quantity);

  /// Sum of line totals before any discount.
  double get subtotal =>
      _lines.values.fold(0.0, (sum, line) => sum + line.lineTotal);

  /// Effective discount, clamped to the subtotal so the bill can never go
  /// negative.
  double get effectiveDiscount => discountAmount.clamp(0, subtotal);

  /// What the customer actually pays after discount, rounded to paise.
  double get payable => (subtotal - effectiveDiscount).clamp(0, double.infinity);

  void add(FoodItemModel item, {int quantity = 1}) {
    final existing = _lines[item.id];
    _lines[item.id] = CartLine(
      foodItem: item,
      quantity: (existing?.quantity ?? 0) + quantity,
    );
    notifyListeners();
  }

  /// Set an explicit quantity (cart UI +/- buttons). Removes the line at 0.
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

  void remove(int foodItemId) {
    if (_lines.remove(foodItemId) != null) {
      notifyListeners();
    }
  }

  /// Clear the whole cart (F2-style "new customer").
  void clear() {
    if (isEmpty) return;
    _lines.clear();
    discountAmount = 0.0;
    customerName = '';
    customerPhone = '';
    upiRef = '';
    paymentMode = PaymentMode.cash;
    orderType = OrderType.takeaway;
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
    discountAmount = clamped;
    notifyListeners();
  }

  void setCustomerName(String name) {
    if (customerName == name) return;
    customerName = name;
    notifyListeners();
  }

  void setCustomerPhone(String phone) {
    if (customerPhone == phone) return;
    customerPhone = phone;
    notifyListeners();
  }

  void setUpiRef(String reference) {
    if (upiRef == reference) return;
    upiRef = reference;
    notifyListeners();
  }
}

/// Replaces the built-in Dart double clamp helpers with a readable inline
/// extension used above (Dart 3.12 core has no `clamp` on num).
extension DoubleClamp on double {
  double clamp(double lower, double upper) =>
      this < lower ? lower : (this > upper ? upper : this);
}