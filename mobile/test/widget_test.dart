// BizBite widget + unit tests.
//
// These tests deliberately avoid the platform secure-storage plugin: they
// exercise pure domain logic (cart maths, wire serialization) and render the
// sign-in screen with an in-memory token store.

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:bizbite_mobile/core/network/dio_client.dart';
import 'package:bizbite_mobile/core/storage/secure_token_storage.dart';
import 'package:bizbite_mobile/core/storage/token_store.dart';
import 'package:bizbite_mobile/features/auth/data/repositories/auth_repository.dart';
import 'package:bizbite_mobile/features/auth/session_controller.dart';
import 'package:bizbite_mobile/features/menu/data/models/food_item_model.dart';
import 'package:bizbite_mobile/features/orders/cart_controller.dart';
import 'package:bizbite_mobile/features/orders/data/models/order_models.dart';
import 'package:bizbite_mobile/presentation/screens/login_screen.dart';

/// In-memory TokenStore so tests never touch Keychain/Keystore.
class _MemoryTokenStore extends TokenStore {
  String? token;
  String? cachedUser;

  @override
  Future<void> saveToken(String value) async => token = value;

  @override
  Future<String?> readToken() async => token;

  @override
  Future<void> deleteToken() async => token = null;

  @override
  Future<void> saveCachedUser(String userJson) async => cachedUser = userJson;

  @override
  Future<String?> readCachedUser() async => cachedUser;

  @override
  Future<void> deleteCachedUser() async => cachedUser = null;

  @override
  Future<void> clearSession() async {
    token = null;
    cachedUser = null;
  }

  @override
  Future<bool> hasSession() async => token != null && token!.isNotEmpty;
}

void main() {
  test('cart accumulates quantities and caps the discount at the subtotal', () {
    final cart = CartController();
    final item =
        FoodItemModel(id: 1, categoryId: 1, name: 'Vada Pav', price: 12.0);

    cart.add(item);
    cart.add(item);
    expect(cart.totalQuantity, 2);
    expect(cart.subtotal, 24.0);

    cart.setDiscountAmount(100);
    expect(cart.effectiveDiscount, 24.0);
    expect(cart.payable, 0.0);

    cart.setQuantity(item.id, 1);
    expect(cart.lines.single.quantity, 1);

    cart.setPaymentMode(PaymentMode.upi);
    cart.setOrderType(OrderType.dineIn);
    expect(cart.paymentMode, PaymentMode.upi);
    expect(cart.orderType, OrderType.dineIn);

    cart.clear();
    expect(cart.isEmpty, true);
  });

  test('PlaceOrderRequest serializes the documented wire shape', () {
    final request = PlaceOrderRequest(
      paymentMode: PaymentMode.upi,
      orderType: OrderType.takeaway,
      items: [OrderLineRequest(id: 12, quantity: 2)],
      discountAmount: 20,
      customerName: 'Amit',
      customerPhone: '98110',
      upiRef: 'UTR123',
      idempotencyKey: 'abc-123',
    );

    final body = request.toJson();
    final items = (body['items'] as List);
    final first = (items[0] as Map);

    expect(body['payment_mode'], 'upi');
    expect(body['order_type'], 'takeaway');
    expect(body['discount_amount'], '20.00');
    expect(body['customer_name'], 'Amit');
    expect(body['upi_ref'], 'UTR123');
    expect(body['idempotency_key'], 'abc-123');
    expect(items.length, 1);
    expect(first['id'], 12);
    expect(first['quantity'], 2);
  });

  testWidgets('sign-in screen renders with inline credentials form', (
          WidgetTester tester) async {
    final session = SessionController(
      tokenStore: _MemoryTokenStore(),
      authRepository: AuthRepository(
        client: DioClient(tokenStorage: SecureTokenStorage()),
      ),
    );

    await tester.pumpWidget(MaterialApp(
      title: 'BizBite test',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.deepOrange),
      ),
      home: LoginScreen(session: session),
    ));

    expect(find.text('BizBite POS'), findsOneWidget);
    expect(find.text('Sign in to start billing'), findsOneWidget);
    expect(find.text('Sign in'), findsOneWidget);
  });
}
