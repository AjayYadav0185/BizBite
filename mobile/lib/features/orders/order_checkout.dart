import '../../core/utils/device_info.dart';
import 'cart_controller.dart';
import 'data/models/cart_line.dart';
import 'data/models/order_models.dart';
import 'data/models/order_receipt_model.dart';
import 'data/repositories/order_repository.dart';

/// Thin settlement coordinator for `POST /api/orders`.
///
/// Builds the client cart into the documented wire shape and forwards it to
/// the Sanctum endpoint. All pricing authority stays on the server (shared
/// `OrderService`), so the only thing this class decides is *what* to send.
class OrderCheckout {
  OrderCheckout({required this._repository});

  final OrderRepository _repository;

  Future<OrderReceiptModel> settle({
    required CartController cart,
    required PaymentMode paymentMode,
    required OrderType orderType,
  }) async {
    return _repository.place(
      PlaceOrderRequest(
        paymentMode: paymentMode,
        orderType: orderType,
        items: cart.lines.map((CartLine line) {
          return OrderLineRequest(id: line.foodItem.id, quantity: line.quantity);
        }).toList(growable: false),
        discountAmount: cart.effectiveDiscount,
        customerName: cart.customerName,
        customerPhone: cart.customerPhone,
        upiRef: cart.upiRef,
        idempotencyKey: DeviceInfo.newIdempotencyKey(),
      ),
    );
  }
}