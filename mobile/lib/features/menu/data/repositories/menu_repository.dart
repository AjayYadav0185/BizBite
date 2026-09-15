import 'package:dio/dio.dart';

import '../../../../core/config/api_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/sync/menu_cache_dao.dart';
import '../../../../core/sync/offline_gateway.dart';
import '../../../../core/sync/offline_queued_exception.dart';
import '../../../../core/sync/offline_sources.dart';
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
///
/// Write path (admin): online the write goes straight through. Offline it is
/// parked in `pending_mutations` AND applied to the cached catalog with a
/// negative placeholder id, so the grid/console show the change immediately;
/// the next successful pull replaces it with the server row.
class MenuRepository {
  MenuRepository({
    required this._client,
    MenuCacheDao? cache,
    OfflineGateway? gateway,
  })  : _cache = cache ?? MenuCacheDao(),
        _gateway = gateway ?? OfflineGateway();

  final DioClient _client;
  final MenuCacheDao _cache;
  final OfflineGateway _gateway;

  MenuCacheDao get cache => _cache;

  /// Fetch from network and persist to cache. Throws [ApiException] offline
  /// so callers can fall back to [loadCached].
  ///
  /// The cache write is best-effort and deliberately OUTSIDE the network
  /// try/catch: a sqflite hiccup must never turn a successful fetch into a
  /// thrown DatabaseException (that leaked raw and left `loading` stuck true).
  Future<MenuResponseModel> fetchMenu() async {
    final MenuResponseModel menu;
    try {
      final response = await _client.get<dynamic>(ApiConfig.menu);
      menu = MenuResponseModel.fromJson(toMap(response.data));
      _gateway.markFresh(OfflineSources.menu);
    } on DioException catch (error) {
      // Report the cached grid honestly, then let the controller decide
      // whether to keep painting it (it does whenever a cache exists).
      _gateway.markServedFromCache(OfflineSources.menu);
      throw apiExceptionFrom(error);
    }

    try {
      await _cache.replaceAll(
        categories: menu.categories,
        items: menu.items,
      );
    } catch (error) {
      // Grid stays correct for this session (menu is already parsed);
      // next successful fetch rewrites the cache.
      assert(() {
        // ignore: avoid_print
        print('MenuCacheDao.replaceAll failed: $error');
        return true;
      }());
    }
    return menu;
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
  ///
  /// Offline: queued + a local placeholder category (negative id) is added to
  /// the cached catalog; throws [OfflineQueuedException] (success for the UI).
  Future<CategoryModel> createCategory({required String name}) async {
    final cleanName = name.trim();
    final body = <String, dynamic>{'name': cleanName};

    CategoryModel? created;
    final queued = await _gateway.queueWhenOffline(
      method: 'POST',
      path: ApiConfig.categories,
      data: body,
      label: 'New category $cleanName',
      send: () async {
        try {
          final response = await _client.post<dynamic>(
            ApiConfig.categories,
            data: body,
          );
          final map = toMap(response.data['category']);
          created = CategoryModel.fromJson(
            map.isEmpty ? toMap(response.data) : map,
          );
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _cache.upsertCategory(
        CategoryModel(id: _cache.localId(), name: cleanName),
      );
      throw OfflineQueuedException(label: 'New category');
    }
    return created!;
  }

  /// Admin-only: rename a category. Offline: queued + cache patched.
  Future<CategoryModel> renameCategory({
    required int id,
    required String name,
  }) async {
    final cleanName = name.trim();
    final body = <String, dynamic>{'name': cleanName};

    CategoryModel? updated;
    final queued = await _gateway.queueWhenOffline(
      method: 'PUT',
      path: ApiConfig.category(id),
      data: body,
      label: 'Category rename',
      send: () async {
        try {
          final response = await _client.put<dynamic>(
            ApiConfig.category(id),
            data: body,
          );
          final map = toMap(response.data['category']);
          updated = map.isEmpty
              ? CategoryModel(id: id, name: cleanName)
              : CategoryModel.fromJson(map);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _cache.renameCategory(id, cleanName);
      throw OfflineQueuedException(label: 'Category rename');
    }
    return updated!;
  }

  /// Admin-only: add a food item. Offline: queued + local placeholder item.
  Future<FoodItemModel> createItem({
    required int categoryId,
    required String name,
    required double price,
  }) async {
    final cleanName = name.trim();
    final body = <String, dynamic>{
      'category_id': categoryId,
      'name': cleanName,
      'price': price,
    };

    Map<String, dynamic>? created;
    final queued = await _gateway.queueWhenOffline(
      method: 'POST',
      path: ApiConfig.menuItems,
      data: body,
      label: 'New item $cleanName',
      send: () async {
        try {
          final response = await _client.post<dynamic>(
            ApiConfig.menuItems,
            data: body,
          );
          created = toMap(response.data['item']);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      final local = FoodItemModel(
        id: _cache.localId(),
        categoryId: categoryId,
        name: cleanName,
        price: price,
      );
      await _cache.upsertItem(local);
      throw OfflineQueuedException(label: 'New item');
    }
    final map = created ?? const {};
    if (map.isEmpty) {
      return FoodItemModel(
        id: 0,
        categoryId: categoryId,
        name: cleanName,
        price: price,
      );
    }
    return FoodItemModel.fromJson(map);
  }

  /// Admin-only: edit name / price / category of a food item.
  /// Offline: queued + the cached row is patched in place.
  Future<FoodItemModel> updateItem({
    required int id,
    required int categoryId,
    required String name,
    required double price,
  }) async {
    final cleanName = name.trim();
    final body = <String, dynamic>{
      'category_id': categoryId,
      'name': cleanName,
      'price': price,
    };

    Map<String, dynamic>? updated;
    final queued = await _gateway.queueWhenOffline(
      method: 'PUT',
      path: ApiConfig.menuItem(id),
      data: body,
      label: 'Item edit $cleanName',
      send: () async {
        try {
          final response = await _client.put<dynamic>(
            ApiConfig.menuItem(id),
            data: body,
          );
          updated = toMap(response.data['item']);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _cache.upsertItem(
        FoodItemModel(
          id: id,
          categoryId: categoryId,
          name: cleanName,
          price: price,
        ),
      );
      throw OfflineQueuedException(label: 'Item edit');
    }
    final map = updated ?? const {};
    if (map.isEmpty) {
      return FoodItemModel(
        id: id,
        categoryId: categoryId,
        name: cleanName,
        price: price,
      );
    }
    return FoodItemModel.fromJson(map);
  }

  /// Admin-only: delete a food item. Offline: queued + removed from the grid.
  Future<void> deleteItem(int id) async {
    final queued = await _gateway.queueWhenOffline(
      method: 'DELETE',
      path: ApiConfig.menuItem(id),
      data: null,
      label: 'Item removed',
      send: () async {
        try {
          await _client.delete<dynamic>(ApiConfig.menuItem(id));
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _cache.removeItem(id);
      throw OfflineQueuedException(label: 'Item delete');
    }
  }
}
