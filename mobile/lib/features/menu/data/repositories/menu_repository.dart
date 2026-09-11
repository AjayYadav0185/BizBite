import 'package:dio/dio.dart';

import '../../../../core/config/api_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/sync/menu_cache_dao.dart';
import '../../../../core/utils/parse_utils.dart';
import '../models/category_model.dart';
import '../models/food_item_model.dart';
import '../models/menu_response_model.dart';

/// Offline-first menu gateway.
///
/// Read path: SQLite last-good cache first (instant POS grid, works in a
/// basement with zero bars), then a background refresh overwrites the cache
/// wholesale — catalog is server-wins (LWW), the admin panel is the sole
/// writer, so no merge is ever needed.
class MenuRepository {
  MenuRepository({required this._client, MenuCacheDao? cache})
      : _cache = cache ?? MenuCacheDao();

  final DioClient _client;
  final MenuCacheDao _cache;

  MenuCacheDao get cache => _cache;

  /// Fetch from network and persist to cache. Throws [ApiException] offline
  /// so callers can fall back to [loadCached].
  Future<MenuResponseModel> fetchMenu() async {
    try {
      final response = await _client.get<dynamic>(ApiConfig.menu);
      final menu = MenuResponseModel.fromJson(toMap(response.data));
      await _cache.replaceAll(
        categories: menu.categories,
        items: menu.items,
      );
      return menu;
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// Instant, offline-safe read of the last-good snapshot.
  Future<MenuResponseModel> loadCached() async {
    final categories = await _cache.readCategories();
    final items = await _cache.readItems();
    return MenuResponseModel(categories: categories, items: items);
  }

  Future<bool> get hasCache => _cache.hasCache;
  Future<DateTime?> get lastSyncAt => _cache.lastSyncAt;

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
      if (map.isEmpty) {
        return FoodItemModel(
          id: 0,
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
