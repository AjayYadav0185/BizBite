import '../../core/utils/device_info.dart';
import '../../core/utils/india.dart';
import 'cart_controller.dart';
import 'data/models/cart_line.dart';
import 'data/models/order_models.dart';
import 'data/models/order_receipt_model.dart';
import 'data/models/store_model.dart';
import 'data/repositories/order_repository.dart';

/// Settlement coordinator with an offline-first fallback.
///
/// Online: returns the authoritative server receipt (201).
/// Offline/timeout: [OrderRepository] queues the payload into the durable
/// outbox and throws [OfflineQueuedException] — this class converts that
/// into a printable LOCAL receipt so the till never blocks, then
/// SyncOrchestrator replays the same idempotency key later.
class OrderCheckout {
  OrderCheckout({required this._repository});

  final OrderRepository _repository;

  /// Saved bill's receipt for reprint / WhatsApp (online only).
  Future<OrderReceiptModel> receiptFor(int orderId) =>
      _repository.fetchReceipt(orderId);

  Future<OrderReceiptModel> settle({
    required CartController cart,
    required PaymentMode paymentMode,
    required OrderType orderType,
    String? idempotencyKey,
    StoreModel? store,
    String cashier = '',
  }) async {
    final key = (idempotencyKey != null && idempotencyKey.trim().isNotEmpty)
        ? idempotencyKey.trim()
        : DeviceInfo.newIdempotencyKey();
    final request = PlaceOrderRequest(
      paymentMode: paymentMode,
      orderType: orderType,
      items: cart.lines.map((CartLine line) {
        return OrderLineRequest(id: line.foodItem.id, quantity: line.quantity);
      }).toList(growable: false),
      discountAmount: cart.effectiveDiscount,
      customerName: cart.customerName,
      customerPhone: cart.customerPhone,
      upiRef: cart.upiRef,
      tableNumber: orderType == OrderType.dineIn ? cart.tableNumber : '',
      campaignCode: cart.campaignCode,
      notes: cart.combinedNotes,
      tenderedAmount:
          paymentMode == PaymentMode.cash ? cart.tenderedAmount : 0,
      splitLegs: paymentMode == PaymentMode.split ? cart.splitLegs : const [],
      deliveryAddress:
          orderType == OrderType.delivery ? cart.deliveryAddress : '',
      // Callers holding a key across a network retry let the server dedupe
      // the bill; callers without one get a fresh key per settlement.
      idempotencyKey: key,
    );
    try {
      return await _repository.place(request, localTotal: cart.payable);
    } on OfflineQueuedException catch (queued) {
      return _localReceipt(
          cart: cart,
          queued: queued,
          request: request,
          store: store,
          cashier: cashier);
    }
  }

  /// Best-effort printable receipt built from the cart snapshot.
  /// Clearly marked LOCAL / pending sync; replaced by the server receipt
  /// once SyncOrchestrator replays the outbox.
  OrderReceiptModel _localReceipt({
    required CartController cart,
    required OfflineQueuedException queued,
    required PlaceOrderRequest request,
    StoreModel? store,
    String cashier = '',
  }) {
    final now = DateTime.now();
    final items = [
      for (final line in cart.lines)
        OrderItemModel(
          foodItemName: line.foodItem.name,
          quantity: line.quantity,
          price: line.foodItem.price,
          subtotal: line.lineTotal,
          gstRate: line.foodItem.gstRate,
        ),
    ];
    final effectiveStore = store ?? const StoreModel(name: '');
    final taxRows = effectiveStore.printsTaxInvoice
        ? _estimateTax(cart)
        : const <TaxRow>[];
    return OrderReceiptModel(
      orderId: 0,
      orderNumber: queued.localBillNo,
      totalAmount: cart.payable,
      paymentMode: request.paymentMode,
      status: 'pending_sync',
      store: effectiveStore,
      cashier: cashier,
      placedAt:
          '${now.day.toString().padLeft(2, '0')}/${now.month.toString().padLeft(2, '0')} ${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')} (offline)',
      totalQuantity: cart.totalQuantity,
      items: items,
      orderType: request.orderType,
      customerName: cart.customerName,
      customerPhone: cart.customerPhone,
      upiRef: cart.upiRef,
      isOffline: true,
      subtotal: cart.subtotal,
      discountAmount: cart.totalDiscount,
      roundOff: cart.roundOff,
      taxBreakup: taxRows,
      taxAmount: taxRows.fold(0.0, (s, r) => s + r.tax),
      tableNumber: request.tableNumber,
      notes: request.notes,
      campaignCode: cart.campaignDiscount > 0 ? cart.campaignCode : '',
      tenderedAmount: request.tenderedAmount,
      changeAmount: request.tenderedAmount > 0 ? cart.changeDue : 0,
      deliveryAddress: request.deliveryAddress,
    );
  }

  /// Offline GST estimate with the server's formula: inclusive tax per line
  /// = amount × rate / (100 + rate), scaled by the bill discount. The
  /// server's numbers replace these once the bill syncs.
  List<TaxRow> _estimateTax(CartController cart) {
    if (cart.subtotal <= 0) return const [];
    final factor = (cart.subtotal - cart.totalDiscount) / cart.subtotal;
    final gross = <int, double>{};
    for (final line in cart.lines) {
      final rate = line.foodItem.gstRate;
      if (rate <= 0) continue;
      gross[rate] = (gross[rate] ?? 0) + line.lineTotal * factor;
    }
    final rates = gross.keys.toList()..sort();
    return [
      for (final rate in rates)
        () {
          final g = India.paise(gross[rate]!);
          final tax = India.paise(g * rate / (100 + rate));
          final cgst = (tax / 2 * 100).truncateToDouble() / 100;
          return TaxRow(
              rate: rate,
              taxable: India.paise(g - tax),
              cgst: cgst,
              sgst: India.paise(tax - cgst));
        }(),
    ];
  }
}
