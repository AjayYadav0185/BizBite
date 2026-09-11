import 'package:dio/dio.dart';

import '../../../../core/config/api_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/utils/parse_utils.dart';
import '../models/auth_response_model.dart';
import '../models/user_model.dart';

/// Thin typed wrapper over the Sanctum auth endpoints.
///
/// Every method either returns a typed model or throws the app-wide
/// [ApiException] (never raw Dio errors) — the ErrorInterceptor already
/// translated wire failures before control returns to this class.
class AuthRepository {
  AuthRepository({required this._client});

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
          'device_id': ?deviceId,
          'platform': ?platform,
          'app_version': ?appVersion,
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

  /// PUT /api/profile — update own display name / phone number.
  /// Returns the refreshed profile exactly as the server stored it.
  Future<UserModel> updateProfile({
    required String name,
    required String phone,
  }) async {
    try {
      final response = await _client.put<dynamic>(
        ApiConfig.profile,
        data: {
          'name': name.trim(),
          if (phone.trim().isNotEmpty) 'phone': phone.trim(),
        },
      );
      return UserModel.fromJson(toMap(toMap(response.data)['user']));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// PUT /api/profile/password — change own password (server re-checks the
  /// current one; 422 with field errors on a wrong current password).
  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    try {
      await _client.put<dynamic>(
        ApiConfig.profilePassword,
        data: {
          'current_password': currentPassword,
          'password': newPassword,
          'password_confirmation': newPassword,
        },
      );
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }
}