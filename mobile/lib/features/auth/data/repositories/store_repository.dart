import 'package:dio/dio.dart';

import '../../../../core/config/api_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/utils/parse_utils.dart';
import '../models/store_profile_model.dart';

/// Thin typed wrapper over the store profile / branding endpoints
/// (`GET|PUT /api/store`, `POST|DELETE /api/store/logo`).
///
/// Every method either returns the refreshed [StoreProfileModel] or throws the
/// app-wide [ApiException] (never a raw Dio error) — the ErrorInterceptor
/// already translated wire failures before control returns to this class.
class StoreRepository {
  StoreRepository({required this._client});

  final DioClient _client;

  /// GET /api/store — own store profile + branding (both staff roles).
  Future<StoreProfileModel> show() async {
    try {
      final response = await _client.get<dynamic>(ApiConfig.store);
      return _parse(response.data);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// PUT /api/store — update the shop details (owner only).
  ///
  /// Only the keys passed through [fields] are written server-side, and blank
  /// values are normalized to NULL there so a cleared field really clears.
  Future<StoreProfileModel> update(Map<String, dynamic> fields) async {
    try {
      final response = await _client.put<dynamic>(
        ApiConfig.store,
        data: fields,
      );
      return _parse(response.data);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// POST /api/store/logo — upload/replace the shop logo (owner only).
  ///
  /// Takes the picked image's bytes + filename (not a file path) so the exact
  /// same call works on Android/iOS (gallery/camera picker) and Flutter Web
  /// (blob URL), where `MultipartFile.fromFile` cannot resolve a path. The
  /// Laravel side validates it with `image|mimes:jpg,jpeg,png,webp|max:2048`.
  Future<StoreProfileModel> uploadLogo({
    required List<int> bytes,
    required String filename,
  }) async {
    try {
      final form = FormData.fromMap({
        'logo': MultipartFile.fromBytes(bytes, filename: filename),
      });

      final response = await _client.post<dynamic>(
        ApiConfig.storeLogo,
        data: form,
        // Override the JSON default: Dio derives the multipart boundary from
        // this option, so it must not be left at `application/json`.
        options: Options(contentType: 'multipart/form-data'),
      );
      return _parse(response.data);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// DELETE /api/store/logo — drop the uploaded logo (owner only).
  Future<StoreProfileModel> removeLogo() async {
    try {
      final response = await _client.delete<dynamic>(ApiConfig.storeLogo);
      return _parse(response.data);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// Every store endpoint answers with `{ "message": ..., "store": {...} }`.
  StoreProfileModel _parse(dynamic data) =>
      StoreProfileModel.fromJson(toMap(toMap(data)['store']));
}