// Offline-first SQLite gateway for the BizBite POS.
//
// Single sqflite database (`bizbite_pos.db`) holding:
//   - `meta`               : kv store (last_menu_sync_ms, menu_version, ...)
//   - `cached_categories`  : last-good copy of GET /api/menu categories
//   - `cached_items`       : last-good copy of GET /api/menu items
//   - `pending_orders`     : durable outbox for POST /api/orders
//
// WAL mode is enabled for lunch-rush write throughput. All money is stored
// as REAL here only because the wire models use double; settlement math
// always happens server-side in OrderService.
import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

class AppDatabase {
  AppDatabase._();
  static final AppDatabase instance = AppDatabase._();

  static const _name = 'bizbite_pos.db';
  static const _version = 1;

  Database? _db;

  Future<Database> get db async {
    final existing = _db;
    if (existing != null) return existing;
    final created = await _open();
    _db = created;
    return created;
  }

  Future<Database> _open() async {
    final dir = await getDatabasesPath();
    final path = p.join(dir, _name);
    final database = await openDatabase(
      path,
      version: _version,
      onConfigure: (db) async {
        // Crash-safe + concurrent read/write during rush hour.
        await db.execute('PRAGMA journal_mode=WAL');
        await db.execute('PRAGMA synchronous=NORMAL');
        await db.execute('PRAGMA foreign_keys=ON');
      },
      onCreate: (db, version) async {
        await db.execute('''
          CREATE TABLE meta(
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
          )
        ''');
        await db.execute('''
          CREATE TABLE cached_categories(
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0
          )
        ''');
        await db.execute('''
          CREATE TABLE cached_items(
            id INTEGER PRIMARY KEY,
            category_id INTEGER,
            name TEXT NOT NULL,
            price REAL NOT NULL DEFAULT 0,
            food_type TEXT NOT NULL DEFAULT 'veg',
            gst_rate INTEGER NOT NULL DEFAULT 5,
            sort_order INTEGER NOT NULL DEFAULT 0,
            updated_at TEXT
          )
        ''');
        await db.execute(
            'CREATE INDEX idx_items_cat ON cached_items(category_id)');
        await db.execute('CREATE INDEX idx_items_name ON cached_items(name)');
        await db.execute('''
          CREATE TABLE pending_orders(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            idempotency_key TEXT NOT NULL UNIQUE,
            payload_json TEXT NOT NULL,
            local_bill_no TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            retry_count INTEGER NOT NULL DEFAULT 0,
            next_retry_at INTEGER NOT NULL DEFAULT 0,
            last_error TEXT NOT NULL DEFAULT '',
            created_at INTEGER NOT NULL
          )
        ''');
        await db.execute(
            'CREATE INDEX idx_outbox_status ON pending_orders(status, next_retry_at)');
      },
    );
    return database;
  }

  // -- kv helpers ------------------------------------------------------

  Future<String?> readMeta(String key) async {
    final database = await db;
    final rows = await database.query(
      'meta',
      where: 'key = ?',
      whereArgs: [key],
      limit: 1,
    );
    if (rows.isEmpty) return null;
    return rows.first['value'] as String?;
  }

  Future<void> writeMeta(String key, String value) async {
    final database = await db;
    await database.insert(
      'meta',
      {'key': key, 'value': value},
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<void> close() async {
    await _db?.close();
    _db = null;
  }
}
