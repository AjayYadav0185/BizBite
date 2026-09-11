import '../../core/utils/device_info.dart';
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

  Future<OrderReceiptModel> settle({
    required CartController cart,
    required PaymentMode paymentMode,
    required OrderType orderType,
    String? idempotencyKey,
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
      // Callers holding a key across a network retry let the server dedupe
      // the bill; callers without one get a fresh key per settlement.
      idempotencyKey: key,
    );
    try {
      return await _repository.place(request);
    } on OfflineQueuedException catch (queued) {
      return _localReceipt(cart: cart, queued: queued, request: request);
    }
  }

  /// Best-effort printable receipt built from the cart snapshot.
  /// Clearly marked LOCAL / pending sync; replaced by the server receipt
  /// once SyncOrchestrator replays the outbox.
  OrderReceiptModel _localReceipt({
    required CartController cart,
    required OfflineQueuedException queued,
    required PlaceOrderRequest request,
  }) {
    final now = DateTime.now();
    final items = [
      for (final line in cart.lines)
        OrderItemModel(
          foodItemName: line.foodItem.name,
          quantity: line.quantity,
          price: line.foodItem.price,
          subtotal: line.lineTotal,
        ),
    ];
    return OrderReceiptModel(
      orderId: 0,
      orderNumber: queued.localBillNo,
      totalAmount: cart.payable,
      paymentMode: request.paymentMode,
      status: 'pending_sync',
      store: const StoreModel(name: ''),
      cashier: '',
      placedAt:
          '${now.day.toString().padLeft(2, '0')}/${now.month.toString().padLeft(2, '0')} ${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')} (offline)',
      totalQuantity: cart.totalQuantity,
      items: items,
      orderType: request.orderType,
      customerName: cart.customerName,
      upiRef: cart.upiRef,
      isOffline: true,
    );
  }
}
