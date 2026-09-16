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
import 'data/repositories/store_repository.dart';

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
    StoreRepository? storeRepository,
    this._client,
  })  : _auth = authRepository,
        _storeApi = storeRepository;

  final TokenStore _tokenStore;
  final AuthRepository _auth;
  final DioClient? _client;

  /// Store profile / branding endpoints (shop details + logo). Optional so
  /// pure-domain callers (widget tests) can build a session without a network
  /// graph; the My Profile screen always gets the real one from `app.dart`.
  final StoreRepository? _storeApi;

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
      // Same for the shop details/branding (logo, address, receipt template).
      unawaited(_revalidateStore());
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

  /// True while the silent post-restore shop-details refresh is in flight.
  bool _revalidatingStore = false;

  /// Silent `GET /api/store` after a cold restore: refreshes the vaulted
  /// branding (logo, address, receipt header/footer) without blocking the
  /// portal. Network failures keep the cached copy — never a sign-out.
  Future<void> _revalidateStore() async {
    if (_revalidatingStore || _storeApi == null) return;
    _revalidatingStore = true;

    try {
      final fresh = await _storeApi.show();
      store = fresh;
      await _cacheStore(fresh);
      notifyListeners();
    } on ApiException {
      // Offline / 5xx: the cached shop details keep rendering.
    } finally {
      _revalidatingStore = false;
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
        await _cacheStore(result.store!);
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

  /// Self-service profile update — persists own name/phone on the server,
  /// refreshes the local + vaulted profile, and notifies the UI.
  ///
  /// Returns the refreshed [UserModel]; throws [ApiException] on failure
  /// (422 validation / network), which the profile screen surfaces inline.
  Future<UserModel> updateProfile({
    required String name,
    required String phone,
  }) async {
    final fresh = await _auth.updateProfile(name: name, phone: phone);
    user = fresh;
    try {
      await _tokenStore.saveCachedUser(json.encode(fresh.toJson()));
    } catch (_) {
      // Vault write failure must not fail an otherwise successful update —
      // the background revalidation re-caches it on the next launch anyway.
    }
    notifyListeners();
    return fresh;
  }

  /// Self-service password change — server verifies the current password
  /// (422 on a wrong one) and hashes the new one. No local state changes.
  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    await _auth.changePassword(
      currentPassword: currentPassword,
      newPassword: newPassword,
    );
  }

  // -------------------------------------------------------------------
  // Store profile / branding ("About shop" + "Manage shop")
  // -------------------------------------------------------------------

  /// Pull the latest shop details/branding from `GET /api/store` (both roles)
  /// and cache them in the vault. Returns the fresh store.
  Future<StoreProfileModel> refreshStore() async {
    final fresh = await _requireStoreApi().show();
    store = fresh;
    await _cacheStore(fresh);
    notifyListeners();
    return fresh;
  }

  /// `PUT /api/store` — persist owner-edited shop details (name, address,
  /// phones, tax ids, UPI VPA, currency, GST defaults, receipt template) and
  /// refresh the in-memory + vaulted store. Throws [ApiException] (403 for a
  /// cashier, 422 with field errors) which the profile screen surfaces inline.
  Future<StoreProfileModel> updateStore(Map<String, dynamic> fields) async {
    final fresh = await _requireStoreApi().update(fields);
    store = fresh;
    await _cacheStore(fresh);
    notifyListeners();
    return fresh;
  }

  /// `POST /api/store/logo` — upload/replace the shop logo (owner only).
  Future<StoreProfileModel> uploadStoreLogo({
    required List<int> bytes,
    required String filename,
  }) async {
    final fresh = await _requireStoreApi().uploadLogo(
      bytes: bytes,
      filename: filename,
    );
    store = fresh;
    await _cacheStore(fresh);
    notifyListeners();
    return fresh;
  }

  /// `DELETE /api/store/logo` — remove the shop logo (owner only).
  Future<StoreProfileModel> removeStoreLogo() async {
    final fresh = await _requireStoreApi().removeLogo();
    store = fresh;
    await _cacheStore(fresh);
    notifyListeners();
    return fresh;
  }

  /// Best-effort vault write: a storage failure must never fail an otherwise
  /// successful update — the boot revalidation re-caches it on next launch.
  Future<void> _cacheStore(StoreProfileModel fresh) async {
    try {
      await _tokenStore.saveCachedStore(json.encode(fresh.toJson()));
    } catch (_) {
      // Ignore: see doc comment.
    }
  }

  StoreRepository _requireStoreApi() {
    final api = _storeApi;
    if (api == null) {
      throw ApiException(
        message: 'Store management is unavailable in this build.',
      );
    }
    return api;
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