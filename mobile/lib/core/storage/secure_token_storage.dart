import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'token_store.dart';

/// Encrypted vault for the Sanctum bearer token (and a cached user profile).
///
/// On Android this lands in the Keystore-backed EncryptedSharedPreferences
/// and on iOS in the Keychain — tokens never touch plain SharedPreferences.
///
/// The token's storage key is versioned so a future logout-everywhere /
/// token-format change can simply bump [_tokenKey] and orphan the old value.
class SecureTokenStorage extends TokenStore {
  SecureTokenStorage({FlutterSecureStorage? storage})
      : _storage = storage ??
            const FlutterSecureStorage(
              aOptions: AndroidOptions(encryptedSharedPreferences: true),
              iOptions: IOSOptions(
                accessibility: KeychainAccessibility.first_unlock_this_device,
              ),
            );

  static const String _tokenKey = 'bizbite.auth.token.v1';
  static const String _cachedUserKey = 'bizbite.auth.user.v1';

  final FlutterSecureStorage _storage;

  /// Persist the Sanctum token returned by `POST /api/login`
  /// (format: `{token_id}|{plain_text_token}`).
  @override
  Future<void> saveToken(String token) => _storage.write(
        key: _tokenKey,
        value: token,
      );

  /// Read the token, or `null` when the device has no session.
  @override
  Future<String?> readToken() => _storage.read(key: _tokenKey);

  /// Remove the session token (logout / session expiry).
  @override
  Future<void> deleteToken() => _storage.delete(key: _tokenKey);

  /// Cache the serialized user profile alongside the token so a cold start
  /// can render the correct portal instantly before revalidating with
  /// `GET /api/user`.
  @override
  Future<void> saveCachedUser(String userJson) => _storage.write(
        key: _cachedUserKey,
        value: userJson,
      );

  @override
  Future<String?> readCachedUser() => _storage.read(key: _cachedUserKey);

  @override
  Future<void> deleteCachedUser() => _storage.delete(key: _cachedUserKey);

  /// Full session wipe.
  @override
  Future<void> clearSession() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _cachedUserKey);
  }

  /// True when the device holds a token (used by the splash/boot flow).
  @override
  Future<bool> hasSession() async {
    final token = await readToken();
    return token != null && token.isNotEmpty;
  }
}
