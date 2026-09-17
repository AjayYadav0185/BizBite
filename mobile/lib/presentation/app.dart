import 'dart:async';

import 'package:flutter/material.dart' hide MenuController;

import '../core/network/dio_client.dart';
import '../core/storage/secure_token_storage.dart';
import '../core/sync/menu_cache_dao.dart';
import '../core/sync/offline_gateway.dart';
import '../core/sync/outbox_dao.dart';
import '../core/sync/sync_controller.dart';
import '../core/sync/sync_orchestrator.dart';
import '../features/auth/data/repositories/auth_repository.dart';
import '../features/auth/data/repositories/store_repository.dart';
import '../features/auth/session_controller.dart';
import '../features/assistant/assistant_controller.dart';
import '../features/assistant/data/repositories/assistant_repository.dart';
import '../features/menu/data/repositories/menu_repository.dart';
import '../features/menu/menu_controller.dart';
import '../features/ops/console_controller.dart';
import '../features/ops/data/repositories/ops_repository.dart';
import '../features/ops/order_queue_controller.dart';
import '../features/ops/reports_controller.dart';
import '../features/ops/shifts_controller.dart';
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
  late OfflineGateway _offline;
  late WalletController _wallet;
  late OrderQueueController _queue;
  late ShiftsController _shifts;
  late ReportsController _reports;
  late ConsoleController _console;
  late AssistantController _assistant;
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
      storeRepository: StoreRepository(client: _client),
      client: _client,
    );

    // -- Offline-first sync engine -------------------------------------
    // Built FIRST: SyncController is the app-wide `OfflineStatusSink`, so every
    // repository reports through it which screens are serving cached data.
    // Every queued row is stamped with the signed-in store id.
    int activeStoreId() => _session.user?.storeId ?? 0;
    _sync = SyncController(client: _client, storeIdProvider: activeStoreId);
    _offline = OfflineGateway(
      statusSink: _sync,
      storeIdProvider: activeStoreId,
    );

    final menuRepository = MenuRepository(client: _client, gateway: _offline);
    final orderRepository = OrderRepository(
      client: _client,
      storeIdProvider: activeStoreId,
    );
    _menu = MenuController(repository: menuRepository);
    _cart = CartController();
    _orderFlow = OrderFlowController();
    _checkout = OrderCheckout(repository: orderRepository);
    _printer = ReceiptPrinter();
    _printSettings = PrintSettings();
    _wallet = WalletController(
      repository: WalletRepository(client: _client, gateway: _offline),
    );

    // -- Ops modules (queue / shifts / reports / console) ---------------
    final opsRepository = OpsRepository(client: _client, gateway: _offline);
    _queue = OrderQueueController(repository: opsRepository)
      ..storeIdProvider = activeStoreId;
    _shifts = ShiftsController(repository: opsRepository);
    _reports = ReportsController(repository: opsRepository);
    _console = ConsoleController(repository: opsRepository);

    // Owner-only AI assistant (server grounds answers in this store's data;
    // online-only — a chat needs the backend to think, nothing to replay).
    _assistant = AssistantController(
      repository: AssistantRepository(client: _client),
    );

    _orchestrator = SyncOrchestrator(
      client: _client,
      outbox: OutboxDao(),
      menuCache: MenuCacheDao(),
      sync: _sync,
      mutations: _offline.mutations,
      cache: _offline.cache,
      storeIdProvider: activeStoreId,
    );
    SyncOrchestrator.register(_orchestrator);
    _sync.onLinkChanged = (hasLink) {
      if (hasLink) _orchestrator.kick();
    };
    _sync.start();
    // Cache hygiene + pending recount on every sign-in / sign-out transition.
    _session.addListener(_onSessionChanged);
    // Opportunistic kick on boot (flushes work queued while the app was dead):
    // recount first so the banner is honest before the first byte is sent.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _sync.refreshQueued();
      _orchestrator.kick();
    });
  }

  bool _wasSignedIn = false;

  /// Session transitions that matter to the offline layer:
  ///
  ///  sign-in  → recount the outbox (this store may have queued work from a
  ///             previous shift) and kick a sync.
  ///  sign-out → drop the READ caches (they belong to the store that just
  ///             signed out) but KEEP both outboxes: they are store-stamped,
  ///             so queued sales resurface — and replay — only for their own
  ///             store. Nothing is ever silently discarded.
  void _onSessionChanged() {
    final signedIn = _session.phase == SessionPhase.online;
    if (signedIn == _wasSignedIn) return;
    _wasSignedIn = signedIn;

    if (signedIn) {
      unawaited(_sync.refreshQueued());
      unawaited(_orchestrator.kick());
    } else {
      unawaited(_offline.cache.clearAll());
      unawaited(_sync.refreshQueued());
    }
  }

  @override
  void dispose() {
    _session.removeListener(_onSessionChanged);
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
          queue: _queue,
          shifts: _shifts,
          reports: _reports,
          console: _console,
          assistant: _assistant,
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
