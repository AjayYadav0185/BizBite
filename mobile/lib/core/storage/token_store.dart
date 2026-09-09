/// Abstraction over wherever the Sanctum bearer token + cached user profile
/// live on the device.
///
/// Production uses [SecureTokenStorage] (Android Keystore-backed
/// EncryptedSharedPreferences / iOS Keychain). Tests and offline dev harnesses
/// can swap in an in-memory implementation without touching platform plugins.
abstract class TokenStore {
  /// Persist the Sanctum token returned by `POST /api/login`
  /// (format: `{token_id}|{plain_text_token}`).
  Future<void> saveToken(String token);

  /// Read the token, or `null` when the device has no session.
  Future<String?> readToken();

  /// Remove the session token (logout / session expiry).
  Future<void> deleteToken();

  /// Cache the serialized user profile alongside the token so a cold start
  /// can render the correct portal instantly before revalidating with
  /// `GET /api/user`.
  Future<void> saveCachedUser(String userJson);

  Future<String?> readCachedUser();

  Future<void> deleteCachedUser();

  /// Full session wipe.
  Future<void> clearSession();

  /// True when the device holds a token (used by the splash/boot flow).
  Future<bool> hasSession();
}