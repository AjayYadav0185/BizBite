import 'dart:convert';

import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import '../../core/network/dio_client.dart';
import '../../core/storage/token_store.dart';
import '../../core/utils/device_info.dart';
import '../../core/utils/parse_utils.dart';
import 'data/models/auth_response_model.dart';
import 'data/models/store_profile_model.dart';
import 'data/models/user_model.dart';
import 'data/repositories/auth_repository.dart';

/// Lifecycle of the authenticated session, mirroring the Laravel web app's
/// `guest` / `auth` middleware split.
///
/// Driving state machine:
///
///   booting ──► signedOut ──► signingIn ──► online ──► signedOut (logout)
///                  ▲                              │
///                  └────── session expired ───────┘
///
/// The UI (BizBiteApp) wraps itself in a `ListenableBuilder` keyed on this
/// controller: every notification rebuilds the visible screen, so the whole
/// app reacts to login/logout with zero manual navigation.
class SessionController with ChangeNotifier {
  SessionController({
    required TokenStore tokenStore,
    required AuthRepository authRepository,
    DioClient? client,
  })  : _tokenStore = tokenStore,
        _auth = authRepository,
        _client = client;

  final TokenStore _tokenStore;
  final AuthRepository _auth;
  final DioClient? _client;

  SessionPhase phase = SessionPhase.booting;

  /// Profile of the signed-in staff member (set right after login).
  UserModel? user;

  /// Store branding block returned by the login endpoint.
  StoreProfileModel? store;

  /// Cashier-safe last failure message (invalid credentials, deactivated
  /// account, network down, ...).
  String? errorMessage;

  bool get isOnline => phase == SessionPhase.online;

  bool get isAdmin => user?.isAdmin ?? false;

  /// Attach the HTTP client late (avoids a construction cycle: the client
  /// needs this controller for session-expiry callbacks and vice versa).
  void attachClient(DioClient client) {
    _activeClient = client;
  }

  DioClient? _activeClient;

  DioClient? get _http => _activeClient ?? _client;

  /// Restore a session on cold start:
  ///  - no token            -> signedOut
  ///  - token + cached user -> online immediately (portal renders instantly)
  ///  - token only          -> revalidate against `GET /api/user`
  Future<void> boot() async {
    if (!await _tokenStore.hasSession()) {
      _setPhase(SessionPhase.signedOut);
      return;
    }

    final cachedUser = await _tokenStore.readCachedUser();
    if (cachedUser != null && cachedUser.trim().isNotEmpty) {
      user = UserModel.fromJson(toMap(json.decode(cachedUser)));
      _setPhase(SessionPhase.online);
      return;
    }

    // Token present but profile missing — ask the server who we are.
    try {
      user = await _auth.me();
      await _tokenStore.saveCachedUser(json.encode(user!.toJson()));
      _setPhase(SessionPhase.online);
    } on ApiException {
      await _tokenStore.clearSession();
      user = null;
      _setPhase(SessionPhase.signedOut);
    }
  }

  /// POST /api/login — exchanges credentials for a Sanctum token.
  Future<void> login({required String email, required String password}) async {
    phase = SessionPhase.signingIn;
    errorMessage = null;
    notifyListeners();

    try {
      final result = await _auth.login(
        email: email,
        password: password,
        deviceId: DeviceInfo.deviceId,
        platform: DeviceInfo.platform,
        appVersion: DeviceInfo.appVersion,
      );
      await _tokenStore.saveToken(result.plainTextToken);
      await _tokenStore.saveCachedUser(json.encode(result.user.toJson()));

      // Mirror the fresh token into the HTTP client so the very next call
      // (menu pre-fetch) carries the Authorization header even before the
      // secure-storage write round-trips through the AuthInterceptor.
      _http?.useTokenForNextRequests(result.plainTextToken);

      user = result.user;
      store = result.store;
      _setPhase(SessionPhase.online);
    } on ApiException catch (error) {
      errorMessage = error.message;
      _setPhase(SessionPhase.signedOut);
    }
  }

  /// Revoke the token server-side (best effort) and wipe local state.
  Future<void> logout() async {
    try {
      await _auth.logout();
    } on ApiException {
      // Network down? We still clear the device — the server token expires
      // on its own; this device is simply signed out.
    }
    await _tokenStore.clearSession();
    user = null;
    store = null;
    errorMessage = null;
    _setPhase(SessionPhase.signedOut);
  }

  /// Fired by the ErrorInterceptor when any authenticated call returns 401.
  void handleSessionExpired() {
    user = null;
    store = null;
    errorMessage = 'Session expired. Please sign in again.';
    _tokenStore.clearSession();
    _setPhase(SessionPhase.signedOut);
  }

  void _setPhase(SessionPhase next) {
    phase = next;
    notifyListeners();
  }
}

enum SessionPhase { booting, signedOut, signingIn, online }