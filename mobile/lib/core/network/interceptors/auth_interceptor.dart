import 'package:dio/dio.dart';

import '../../config/api_config.dart';
import '../../storage/secure_token_storage.dart';

/// Attaches the Sanctum bearer token to every outgoing request.
///
/// Auth flow: `POST /api/login` returns `{ "token": "12|abcdef..." }`; the
/// token is persisted in [SecureTokenStorage] and this interceptor mirrors it
/// into the `Authorization: Bearer <token>` header on each call — exactly
/// what `auth:sanctum` middleware on the Laravel side expects.
///
/// The login endpoint itself is skipped (there is no token yet) as is any
/// absolute-URL call, so third-party hosts can never accidentally receive
/// the BizBite session token.
class AuthInterceptor extends Interceptor {
  AuthInterceptor(this._tokenStorage);

  final SecureTokenStorage _tokenStorage;

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    final isLoginCall = options.path == ApiConfig.login;
    final isAbsoluteUrl = Uri.parse(options.path).hasScheme;

    if (!isLoginCall && !isAbsoluteUrl) {
      // The vault can throw after a force-kill (Android AEADBadTagException,
      // missing macOS keychain entitlements). That must degrade to an
      // anonymous request (the server answers 401) — never hang or abort
      // the interceptor chain, which would freeze every loading spinner.
      String? token;
      try {
        token = await _tokenStorage.readToken();
      } catch (_) {
        token = null;
      }

      if (token != null && token.isNotEmpty) {
        options.headers['Authorization'] = 'Bearer $token';
      }
    }

    // Guarantee JSON content negotiation even if a caller forgot headers.
    options.headers.addAll({
      'Accept': 'application/json',
      ...options.method == 'GET' || options.data == null
          ? const {}
          : const {'Content-Type': 'application/json'},
    });

    handler.next(options);
  }
}
