import 'package:flutter/foundation.dart';

/// Single source of truth for the BizBite Laravel backend location.
///
/// The Laravel API is served by MAMP. Point [baseUrl] at the machine that
/// runs the backend:
///
///   - Android emulator  -> http://10.0.2.2/BizBite/public/api
///     (10.0.2.2 is the emulator's alias for the host loopback interface)
///   - iOS simulator     -> http://127.0.0.1/BizBite/public/api
///   - Physical tablets  -> http://`<YOUR-LAN-IP>`/BizBite/public/api
///     (the tablet and the MAMP host must share the same Wi-Fi/LAN)
///
/// Sanctum routes live under `/api` (see `bootstrap/app.php` withRouting),
/// so every endpoint below is already relative to the `/api` prefix.
class ApiConfig {
  ApiConfig._();

  /// Backend root for ALL API calls. No trailing slash.
  // static const String baseUrl = 'http://10.20.1.21:8080/api';
  static const String baseUrl = 'https://bizbite.onrender.com/api';
  

  /// Standard headers every BizBite request carries.
  static const Map<String, String> defaultHeaders = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };

  // --------------------------------------------------------------------------
  // Endpoint map — keep in lock-step with routes/api.php
  // --------------------------------------------------------------------------
  static const String login = '/login';
  static const String logout = '/logout';
  static const String me = '/user';
  static const String menu = '/menu';
  static const String orders = '/orders';

  // Ops: kitchen/counter queue + status transitions + refunds + delivery
  // (see routes/api.php — role:admin,cashier).
  static String orderStatus(int orderId) => '/orders/$orderId/status';
  static String orderRefund(int orderId) => '/orders/$orderId/refund';
  static String orderDelivery(int orderId) => '/orders/$orderId/delivery';

  // Shifts: cash-drawer sessions (open / close / list).
  static const String shifts = '/shifts';
  static const String shiftOpen = '/shifts/open';
  static String shiftClose(int shiftId) => '/shifts/$shiftId/close';

  // Owner reports (hourly / best-sellers / range).
  static const String reportsHourly = '/reports/hourly';
  static const String reportsBestSellers = '/reports/best-sellers';
  static const String reportsRange = '/reports/range';

  // Owner-managed dining tables (read for all staff, writes admin-only).
  static const String tables = '/tables';
  static String table(int id) => '/tables/$id';

  // Owner-managed campaigns (read for all staff, writes admin-only).
  static const String campaigns = '/campaigns';
  static String campaign(int id) => '/campaigns/$id';

  // Staff management (admin-only).
  static const String staff = '/staff';
  static String staffMember(int id) => '/staff/$id';

  /// Self-service profile management (own details only).
  static const String profile = '/profile';
  static const String profilePassword = '/profile/password';

  // Customer Wallet (see routes/api.php — role:admin,cashier).
  static const String walletBalance = '/wallet/balance';
  static const String walletRechargeInitiate = '/wallet/recharge/initiate';
  static const String walletRechargeVerify = '/wallet/recharge/verify';

  // Admin-only menu writes (role:admin, see routes/api.php).
  static const String categories = '/categories';
  static String category(int id) => '/categories/$id';
  static const String menuItems = '/menu/items';
  static String menuItem(int id) => '/menu/items/$id';

  /// Network timeouts tuned for outlet Wi-Fi (can be flaky during rush hour).
  ///
  /// Offline rule of thumb: these MUST stay shorter than the time a cashier
  /// can afford to wait for a bill. When they fire, the offline layer takes
  /// over — the cached grid paints and writes go to the durable outbox.
  /// Timeouts are therefore a *failover speed* knob, not a patience knob.
  static const Duration connectTimeout = Duration(seconds: 8);
  static const Duration sendTimeout = Duration(seconds: 15);
  static const Duration receiveTimeout = Duration(seconds: 20);

  /// Verbose wire-level logging in debug builds only.
  static bool get isVerboseLogging => kDebugMode;
}
