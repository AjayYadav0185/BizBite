import 'package:flutter/material.dart' hide MenuController;

import '../core/network/dio_client.dart';
import '../core/storage/secure_token_storage.dart';
import '../core/sync/menu_cache_dao.dart';
import '../core/sync/outbox_dao.dart';
import '../core/sync/sync_controller.dart';
import '../core/sync/sync_orchestrator.dart';
import '../features/auth/data/repositories/auth_repository.dart';
import '../features/auth/session_controller.dart';
import '../features/menu/data/repositories/menu_repository.dart';
import '../features/menu/menu_controller.dart';
import '../features/orders/cart_controller.dart';
import '../features/orders/data/repositories/order_repository.dart';
import '../features/orders/order_checkout.dart';
import '../features/orders/order_flow_controller.dart';
import '../features/wallet/data/repositories/wallet_repository.dart';
import '../features/wallet/wallet_controller.dart';
import 'home_screen.dart';
import 'screens/login_screen.dart';
import 'screens/receipt_screen.dart';
import 'screens/splash_screen.dart';
import 'services/print_settings.dart';
import 'services/receipt_printer.dart';
import 'theme/bizbite_theme.dart';

/// Root of the BizBite mobile POS.
///
/// Owns the dependency graph exactly once (network client, secure token
/// vault, session/menu/cart controllers, checkout + printer services,
/// offline-first sync engine) and presents a single reactive router:
///
///   session ──► (booting → Splash, signedOut → Login, online → portal)
///   portal  ──► orderFlow ──► (pending receipt → Receipt, else → Home tabs)
///
/// Offline-first wiring (see docs/OFFLINE_FIRST.md):
///   SyncController (connectivity + banner) + SyncOrchestrator (outbox
///   replay + menu pull) are created here, the orchestrator is registered
///   as a singleton, and connectivity events auto-kick a sync.
///
/// Theme: single source `lib/presentation/theme/bizbite_theme.dart`
/// (spec docs/design/POS_UI_DESIGN_SPEC.md) — Material3 + Poppins,
/// theme/darkTheme/themeMode via [ThemeProvider] (persisted).
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
  late PrintSettings _printSettings;
  late SyncController _sync;
  late SyncOrchestrator _orchestrator;
  late WalletController _wallet;
  final ThemeProvider _theme = ThemeProvider();

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
    final menuRepository = MenuRepository(client: _client);
    final orderRepository = OrderRepository(client: _client);
    _menu = MenuController(repository: menuRepository);
    _cart = CartController();
    _orderFlow = OrderFlowController();
    _checkout = OrderCheckout(repository: orderRepository);
    _printer = ReceiptPrinter();
    _printSettings = PrintSettings();
    _wallet = WalletController(
      repository: WalletRepository(client: _client),
    );

    // -- Offline-first sync engine -------------------------------------
    _sync = SyncController(client: _client);
    _orchestrator = SyncOrchestrator(
      client: _client,
      outbox: OutboxDao(),
      menuCache: MenuCacheDao(),
      sync: _sync,
    );
    SyncOrchestrator.register(_orchestrator);
    _sync.onLinkChanged = (hasLink) {
      if (hasLink) _orchestrator.kick();
    };
    _sync.start();
    // Opportunistic kick on boot (flushes bills queued while app was dead).
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _orchestrator.kick();
      OutboxDao().pendingCount().then(_sync.setPendingCount);
    });
  }

  @override
  void dispose() {
    _sync.dispose();
    super.dispose();
  }

  Widget _authScreen() {
    return switch (_session.phase) {
      SessionPhase.booting => SplashScreen(session: _session),
      SessionPhase.signedOut ||
      SessionPhase.signingIn => LoginScreen(session: _session),
      SessionPhase.online => SizedBox.shrink(),
    };
  }

  Widget _portal() {
    return ListenableBuilder(
      listenable: _orderFlow,
      builder: (context, child) {
        final receipt = _orderFlow.pendingReceipt;
        // A settled-while-preview-ON receipt always shows its preview screen
        // (toggling the switch mid-preview never yanks it away — the new
        // value applies to the *next* bill, decided in HomeScreen._onSettled).
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
          printSettings: _printSettings,
          sync: _sync,
          orchestrator: _orchestrator,
          wallet: _wallet,
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: Listenable.merge([_session, _theme]),
      builder: (context, _) => MaterialApp(
        title: 'BizBite',
        theme: BizBiteTheme.light(),
        darkTheme: BizBiteTheme.dark(),
        themeMode: _theme.mode,
        home: _session.phase == SessionPhase.online ? _portal() : _authScreen(),
        builder: (context, child) => MediaQuery(
          data: MediaQuery.of(
            context,
          ).copyWith(textScaler: TextScaler.noScaling),
          child: child!,
        ),
      ),
    );
  }
}
