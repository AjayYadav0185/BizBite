import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import '../../core/network/dio_client.dart';
import '../../core/storage/token_store.dart';
import '../../core/utils/device_info.dart';
import '../../core/utils/parse_utils.dart';
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
class SessionController with ChangeNotifier implements Listenable {
  SessionController({
    required this._tokenStore,
    required AuthRepository authRepository,
    this._client,
  }) : _auth = authRepository;

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

  bool _booted = false;

  /// True while the silent post-restore profile refresh is in flight (guards
  /// against duplicate `GET /api/user` calls).
  bool _revalidating = false;

  /// Attach the HTTP client late (avoids a construction cycle: the client
  /// needs this controller for session-expiry callbacks and vice versa).
  void attachClient(DioClient client) {
    _activeClient = client;
  }

  DioClient? _activeClient;

  DioClient? get _http => _activeClient ?? _client;

  /// Restore a session on cold start (app killed & reopened):
  ///
  ///  - vault broken / no token   -> signedOut (never stuck on the splash)
  ///  - token + cached profile    -> online immediately, store restored too
  ///  - token only                -> revalidate against `GET /api/user`
  ///  - cached profile            -> silent background revalidation
  ///
  /// Every storage read is guarded: `flutter_secure_storage` can throw after
  /// a force-kill (Android `AEADBadTagException`, missing macOS keychain
  /// entitlements) — that must surface as a fresh sign-in, not a hung splash.
  Future<void> boot() async {
    if (_booted) return;
    _booted = true;

    var hasToken = false;
    String? cachedUserJson;
    String? cachedStoreJson;

    try {
      hasToken = await _tokenStore.hasSession();
      if (hasToken) {
        cachedUserJson = await _tokenStore.readCachedUser();
        cachedStoreJson = await _tokenStore.readCachedStore();
      }
    } catch (_) {
      // Unreadable vault (platform plugin / key-tag failure): treat as no
      // local session; the login screen renders instead of hanging.
      hasToken = false;
      cachedUserJson = null;
      cachedStoreJson = null;
    }

    if (!hasToken) {
      _setPhase(SessionPhase.signedOut);
      return;
    }

    final userFromCache = _decodeUser(cachedUserJson);
    final storeFromCache = _decodeStore(cachedStoreJson);

    if (userFromCache != null) {
      user = userFromCache;
      store = storeFromCache;
      _setPhase(SessionPhase.online);
      // Keep name/role fresh and verify the token still lives server-side.
      unawaited(_revalidateProfile());
      return;
    }

    // Token present but the cached profile is missing/corrupt — ask the
    // server who we are before entering the portal.
    try {
      user = await _auth.me();
      await _tokenStore.saveCachedUser(json.encode(user!.toJson()));
      _setPhase(SessionPhase.online);
    } on ApiException catch (exception) {
      // Only a genuinely dead token wipes the device — a flaky outlet
      // Wi-Fi must not erase a still-valid session (token stays vaulted,
      // next successful login overwrites it anyway).
      if (exception.isAuthError) {
        await _tokenStore.clearSession();
      }
      user = null;
      store = null;
      _setPhase(SessionPhase.signedOut);
    }
  }

  /// Silent `GET /api/user` after a cold restore: refreshes the cached
  /// profile and force-signs the device out only when the token is dead.
  Future<void> _revalidateProfile() async {
    if (_revalidating) return;
    _revalidating = true;

    try {
      final fresh = await _auth.me();
      user = fresh;
      await _tokenStore.saveCachedUser(json.encode(fresh.toJson()));
      notifyListeners();
    } on ApiException catch (exception) {
      if (exception.isAuthError && isOnline) {
        // Token revoked / account deactivated while we were showing the
        // cached portal — flush the device to the login screen.
        handleSessionExpired();
      }
      // Network / 5xx: the cached portal keeps running on the vaulted token.
    } finally {
      _revalidating = false;
    }
  }

  /// Defensive cached-profile decode — a corrupt vault entry must fall back
  /// to server revalidation, never crash the boot sequence.
  UserModel? _decodeUser(String? cachedJson) {
    if (cachedJson == null || cachedJson.trim().isEmpty) return null;
    try {
      return UserModel.fromJson(toMap(json.decode(cachedJson)));
    } on FormatException {
      return null;
    }
  }

  StoreProfileModel? _decodeStore(String? cachedJson) {
    if (cachedJson == null || cachedJson.trim().isEmpty) return null;
    try {
      return StoreProfileModel.fromJson(toMap(json.decode(cachedJson)));
    } on FormatException {
      return null;
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
      if (result.store != null) {
        await _tokenStore.saveCachedStore(json.encode(result.store!.toJson()));
      }

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