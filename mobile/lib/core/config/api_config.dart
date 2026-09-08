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
  static const String baseUrl = 'http://10.0.2.2/BizBite/public/api';

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

  /// Network timeouts tuned for outlet Wi-Fi (can be flaky during rush hour).
  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration sendTimeout = Duration(seconds: 20);
  static const Duration receiveTimeout = Duration(seconds: 30);

  /// Verbose wire-level logging in debug builds only.
  static bool get isVerboseLogging => kDebugMode;
}
