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

  /// Admin-only: create a category. 403 when a cashier token calls it.
  Future<CategoryModel> createCategory({required String name}) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.categories,
        data: {'name': name.trim()},
      );
      final map = toMap(response.data['category']);
      return CategoryModel.fromJson(map.isEmpty ? toMap(response.data) : map);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// Admin-only: rename a category.
  Future<CategoryModel> renameCategory({
    required int id,
    required String name,
  }) async {
    try {
      final response = await _client.put<dynamic>(
        ApiConfig.category(id),
        data: {'name': name.trim()},
      );
      final map = toMap(response.data['category']);
      if (map.isEmpty) return CategoryModel(id: id, name: name.trim());
      return CategoryModel.fromJson(map);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// Admin-only: add a food item.
  Future<FoodItemModel> createItem({
    required int categoryId,
    required String name,
    required double price,
  }) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.menuItems,
        data: {
          'category_id': categoryId,
          'name': name.trim(),
          'price': price,
        },
      );
      final map = toMap(response.data['item']);
      return FoodItemModel.fromJson(map.isEmpty ? toMap(response.data) : map);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// Admin-only: edit name / price / category of a food item.
  Future<FoodItemModel> updateItem({
    required int id,
    required int categoryId,
    required String name,
    required double price,
  }) async {
    try {
      final response = await _client.put<dynamic>(
        ApiConfig.menuItem(id),
        data: {
          'category_id': categoryId,
          'name': name.trim(),
          'price': price,
        },
      );
      final map = toMap(response.data['item']);
      if (map.isEmpty) {
        return FoodItemModel(
          id: id,
          categoryId: categoryId,
          name: name.trim(),
          price: price,
        );
      }
      return FoodItemModel.fromJson(map);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// Admin-only: delete a food item.
  Future<void> deleteItem(int id) async {
    try {
      await _client.delete<dynamic>(ApiConfig.menuItem(id));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }
}