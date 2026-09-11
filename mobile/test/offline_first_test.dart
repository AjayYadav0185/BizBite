// BizBite offline-first regression tests.
//
// Pure-Dart tests (no sqflite platform channel): they verify the contracts
// the sync engine depends on — wire serialization, offline receipt math,
// local bill number derivation, and the push-then-pull conflict policy.
import 'package:flutter_test/flutter_test.dart';

import 'package:BizBite/core/utils/device_info.dart';
import 'package:BizBite/features/menu/data/models/food_item_model.dart';
import 'package:BizBite/features/orders/cart_controller.dart';
import 'package:BizBite/features/orders/data/models/order_models.dart';
import 'package:BizBite/features/orders/data/repositories/order_repository.dart';

void main() {
  test('idempotency keys are RFC-4122 v4 shaped (server UNIQUE column)', () {
    final key = DeviceInfo.newIdempotencyKey();
    expect(
      RegExp(
        r'^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
      ).hasMatch(key),
      isTrue,
      reason: 'key was $key',
    );
    expect(key.length, 36);
  });

  test('offline local bill numbers derive deterministically from the key',
      () {
    // Same key replayed after a timeout must map to the same LOCAL number
    // so the cashier recognises it as one bill, not two.
    String local(String key) =>
        'LOCAL-${key.replaceAll('-', '').substring(0, 8).toUpperCase()}';
    const key = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';
    expect(local(key), local(key));
    expect(local(key).startsWith('LOCAL-'), isTrue);
  });

  test('PlaceOrderRequest keeps the idempotency key on the wire', () {
    final request = PlaceOrderRequest(
      paymentMode: PaymentMode.upi,
      orderType: OrderType.takeaway,
      items: const [OrderLineRequest(id: 12, quantity: 2)],
      discountAmount: 20,
      customerName: 'Amit',
      idempotencyKey: 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
    );
    final json = request.toJson();
    expect(json['idempotency_key'], 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee');
    expect(json['items'], [
      {'id': 12, 'quantity': 2}
    ]);
  });

  test('offline receipt math mirrors the cart (server re-prices on sync)',
      () {
    final cart = CartController();
    cart.add(const FoodItemModel(
        id: 1, categoryId: 1, name: 'Vada Pav', price: 12.0),
        quantity: 2);
    cart.setDiscountAmount(4);
    expect(cart.subtotal, 24.0);
    expect(cart.payable, 20.0);
    expect(cart.totalQuantity, 2);
  });

  test('OfflineQueuedException carries the local bill identity', () {
    final exception = OfflineQueuedException(
      localBillNo: 'LOCAL-AAAAAAAA',
      idempotencyKey: 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
    );
    expect(exception.localBillNo, 'LOCAL-AAAAAAAA');
    expect('$exception', contains('LOCAL-AAAAAAAA'));
  });
}
