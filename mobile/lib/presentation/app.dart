import 'package:flutter/material.dart' hide MenuController;

import '../core/network/dio_client.dart';
import '../core/storage/secure_token_storage.dart';
import '../features/auth/data/repositories/auth_repository.dart';
import '../features/auth/session_controller.dart';
import '../features/menu/data/repositories/menu_repository.dart';
import '../features/menu/menu_controller.dart';
import '../features/orders/cart_controller.dart';
import '../features/orders/data/repositories/order_repository.dart';
import '../features/orders/order_checkout.dart';
import '../features/orders/order_flow_controller.dart';
import 'home_screen.dart';
import 'screens/login_screen.dart';
import 'screens/receipt_screen.dart';
import 'screens/splash_screen.dart';
import 'services/receipt_printer.dart';
import 'theme/bizbite_theme.dart';

/// Root of the BizBite mobile POS.
///
/// Owns the dependency graph exactly once (network client, secure token
/// vault, session/menu/cart controllers, checkout + printer services) and
/// presents a single reactive router:
///
///   session ──► (booting → Splash, signedOut → Login, online → portal)
///   portal  ──► orderFlow ──► (pending receipt → Receipt, else → Home tabs)
///
/// No Navigator is needed: swapping the widget produced by the router IS the
/// navigation.
class BizBiteApp extends StatefulWidget {
  const BizBiteApp({super.key});

  @override
  State<BizBiteApp> createState() => _BizBiteAppState();
}

class _BizBiteAppState extends State<BizBiteApp> {
  late SecureTokenStorage _tokenStore;
  late DioClient _client;
  late SessionController _session;
  late MenuController _menu;
  late CartController _cart;
  late OrderFlowController _orderFlow;
  late OrderCheckout _checkout;
  late ReceiptPrinter _printer;

  @override
  void initState() {
    super.initState();

    _tokenStore = SecureTokenStorage();
    _client = DioClient(
      tokenStorage: _tokenStore,
      onSessionExpired: (exception) => _session.handleSessionExpired(),
    );

    _session = SessionController(
      tokenStore: _tokenStore,
      authRepository: AuthRepository(client: _client),
      client: _client,
    );
    _menu = MenuController(repository: MenuRepository(client: _client));
    _cart = CartController();
    _orderFlow = OrderFlowController();
    _checkout = OrderCheckout(repository: OrderRepository(client: _client));
    _printer = ReceiptPrinter();
  }

  Widget _authScreen() {
    return switch (_session.phase) {
      SessionPhase.booting => SplashScreen(session: _session),
      SessionPhase.signedOut || SessionPhase.signingIn =>
        LoginScreen(session: _session),
      SessionPhase.online => SizedBox.shrink(),
    };
  }

  Widget _portal() {
    return ListenableBuilder(
      listenable: _orderFlow,
      builder: (context, child) {
        final receipt = _orderFlow.pendingReceipt;
        if (receipt != null) {
          return ReceiptScreen(
            receipt: receipt,
            session: _session,
            orderFlow: _orderFlow,
            printer: _printer,
          );
        }
        return HomeScreen(
          session: _session,
          menu: _menu,
          cart: _cart,
          orderFlow: _orderFlow,
          checkout: _checkout,
          printer: _printer,
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'BizBite',
      theme: BizBiteTheme.light(),
      home: ListenableBuilder(
        listenable: _session,
        builder: (context, child) {
          return _session.phase == SessionPhase.online
              ? _portal()
              : _authScreen();
        },
      ),
    );
  }
}