import 'package:dio/dio.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/utils/device_info.dart';
import '../../../../core/utils/parse_utils.dart';
import '../config/api_config.dart';
import 'auth_response_model.dart';
import 'user_model.dart';

/// Thin typed wrapper over the Sanctum auth endpoints.
///
/// Every method either returns a typed model or throws the app-wide
/// [ApiException] (never raw Dio errors) — the ErrorInterceptor already
/// translated wire failures before control returns to this class.
class AuthRepository {
  AuthRepository({required DioClient client}) : _client = client;

  final DioClient _client;

  /// POST /api/login — issue a Sanctum bearer token + user profile.
  ///
  /// `device_id` / `platform` / `app_version` bind this Flutter device to the
  /// store on the Laravel side (StoreDevice table, used for FCM + offline
  /// bookkeeping).
  Future<AuthResponseModel> login({
    required String email,
    required String password,
    String? deviceId,
    String? platform,
    String? appVersion,
  }) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.login,
        data: {
          'email': email.trim(),
          'password': password,
          if (deviceId != null) 'device_id': deviceId,
          if (platform != null) 'platform': platform,
          if (appVersion != null) 'app_version': appVersion,
        },
      );

      return AuthResponseModel.fromJson(toMap(response.data));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// GET /api/user — revalidate the stored token and refresh the profile.
  Future<UserModel> me() async {
    try {
      final response = await _client.get<dynamic>(ApiConfig.me);
      return UserModel.fromJson(toMap(response.data));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// GET /api/logout — revoke the current Sanctum token server-side.
  ///
  /// Safe to call with the token already expired: the endpoint is idempotent
  /// and the client always clears local storage afterwards regardless.
  Future<void> logout() async {
    try {
      await _client.post<dynamic>(ApiConfig.logout);
    } on DioException catch (error) {
      // Even a network failure must not block local session teardown —
      // callers swallow this, but keep the type honest for logging.
      throw apiExceptionFrom(error);
    }
  }
}