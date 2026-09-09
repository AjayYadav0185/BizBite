import 'package:dio/dio.dart';

import '../../../../core/config/api_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/utils/parse_utils.dart';
import '../models/menu_response_model.dart';

/// Thin typed wrapper over `GET /api/menu`.
///
/// The backend already constrains categories + items to the caller's store
/// (global StoreScope), filters availability and pre-sorts, so this class only
/// maps JSON onto the typed [MenuResponseModel].
class MenuRepository {
  MenuRepository({required this._client});

  final DioClient _client;

  Future<MenuResponseModel> fetchMenu() async {
    try {
      final response = await _client.get<dynamic>(ApiConfig.menu);
      return MenuResponseModel.fromJson(toMap(response.data));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }
}