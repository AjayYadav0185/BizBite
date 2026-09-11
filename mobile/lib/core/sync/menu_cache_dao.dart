// DAO for the last-good menu snapshot (`GET /api/menu`).
//
// Write path: `replaceAll()` inside ONE transaction so the POS grid never
// sees a half-written menu. Read path: plain SELECTs ordered exactly like
// the server (sort_order, name) so online and offline grids look identical.
import 'package:sqflite/sqflite.dart';

import '../../features/menu/data/models/category_model.dart';
import '../../features/menu/data/models/food_item_model.dart';
import 'app_database.dart';

class MenuCacheDao {
  MenuCacheDao({AppDatabase? database}) : _db = database ?? AppDatabase.instance;

  final AppDatabase _db;

  static const keyLastSyncMs = 'last_menu_sync_ms';

  Future<void> replaceAll({
    required List<CategoryModel> categories,
    required List<FoodItemModel> items,
  }) async {
    final database = await _db.db;
    await database.transaction((txn) async {
      await txn.delete('cached_categories');
      await txn.delete('cached_items');
      for (final category in categories) {
        await txn.insert(
          'cached_categories',
          {'id': category.id, 'name': category.name, 'sort_order': 0},
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
      for (final item in items) {
        await txn.insert(
          'cached_items',
          {
            'id': item.id,
            'category_id': item.categoryId,
            'name': item.name,
            'price': item.price,
            'food_type': item.foodType,
            'gst_rate': item.gstRate,
            'sort_order': item.sortOrder,
          },
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
    });
    await _db.writeMeta(
      keyLastSyncMs,
      DateTime.now().millisecondsSinceEpoch.toString(),
    );
  }

  Future<List<CategoryModel>> readCategories() async {
    final database = await _db.db;
    final rows = await database.query(
      'cached_categories',
      orderBy: 'sort_order ASC, name ASC',
    );
    return [
      for (final row in rows)
        CategoryModel(
          id: (row['id'] as num?)?.toInt() ?? 0,
          name: (row['name'] ?? '').toString(),
        ),
    ];
  }

  Future<List<FoodItemModel>> readItems() async {
    final database = await _db.db;
    final rows = await database.query(
      'cached_items',
      orderBy: 'sort_order ASC, name ASC',
    );
    return [
      for (final row in rows)
        FoodItemModel(
          id: (row['id'] as num?)?.toInt() ?? 0,
          categoryId: (row['category_id'] as num?)?.toInt() ?? 0,
          name: (row['name'] ?? '').toString(),
          price: (row['price'] as num?)?.toDouble() ?? 0.0,
          foodType: (row['food_type'] ?? 'veg').toString(),
          gstRate: (row['gst_rate'] as num?)?.toInt() ?? 5,
          sortOrder: (row['sort_order'] as num?)?.toInt() ?? 0,
        ),
    ];
  }

  Future<bool> get hasCache async {
    final database = await _db.db;
    final result = await database.rawQuery(
      'SELECT COUNT(*) AS c FROM cached_items',
    );
    final count = (result.first['c'] as num?)?.toInt() ?? 0;
    return count > 0;
  }

  Future<DateTime?> get lastSyncAt async {
    final raw = await _db.readMeta(keyLastSyncMs);
    final ms = int.tryParse(raw ?? '');
    if (ms == null) return null;
    return DateTime.fromMillisecondsSinceEpoch(ms);
  }
}
