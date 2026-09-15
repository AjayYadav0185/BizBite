// Offline-first SQLite gateway for the BizBite POS.
//
// Single sqflite database (`bizbite_pos.db`) holding:
//   - `meta`               : kv store (last_menu_sync_ms, menu_version, ...)
//   - `cached_categories`  : last-good copy of GET /api/menu categories
//   - `cached_items`       : last-good copy of GET /api/menu items
//   - `pending_orders`     : durable outbox for POST /api/orders
//   - `cache_entries`      : v2 — last-good RAW envelope for EVERY other
//                            screen (queue, shifts, reports, tables,
//                            campaigns, staff, wallet)
//   - `pending_mutations`  : v2 — durable outbox for every other write
//                            (status moves, shifts, console, menu admin)
//
// WAL mode is enabled for lunch-rush write throughput. All money is stored
// as REAL here only because the wire models use double; settlement math
// always happens server-side in OrderService.
import 'dart:async';

import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

class AppDatabase {
  AppDatabase._();
  static final AppDatabase instance = AppDatabase._();

  static const _name = 'bizbite_pos.db';

  /// v1 = menu cache + bill outbox.
  /// v2 = + generic screen cache (`cache_entries`) and multi-verb outbox
  ///      (`pending_mutations`) so every screen works offline.
  static const _version = 2;

  Database? _db;

  /// Single-flight open: `MenuRepository.loadCached()`, `SyncOrchestrator.kick()`
  /// and `OutboxDao.pendingCount()` all hit this getter concurrently on boot.
  /// A plain lazy getter would call `openDatabase()` twice for the same file
  /// ("database is locked" → uncaught → loading spinners never resolve).
  Completer<Database>? _opening;

  Future<Database> get db async {
    final existing = _db;
    if (existing != null) return existing;
    final opening = _opening;
    if (opening != null) return opening.future;

    final completer = Completer<Database>();
    _opening = completer;
    _open().then((database) {
      _db = database;
      completer.complete(database);
    }).catchError((Object error, StackTrace stackTrace) {
      // Allow the next caller to retry the open from scratch.
      _opening = null;
      completer.completeError(error, stackTrace);
    });
    return completer.future;
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
        await _createV1(db);
        await _createV2(db);
      },
      onUpgrade: (db, oldVersion, newVersion) async {
        // Existing tablets upgrade in place — queued bills in `pending_orders`
        // survive, so a mid-service app update never loses a sale.
        if (oldVersion < 2) await _createV2(db);
      },
    );
    return database;
  }

  // -- schema ----------------------------------------------------------

  Future<void> _createV1(Database db) async {
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
  }

  /// v2 additions — idempotent (`IF NOT EXISTS` + PRAGMA check) so both
  /// `onCreate` and the `onUpgrade` path can call it safely on any device.
  Future<void> _createV2(Database db) async {
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cache_entries(
        key TEXT PRIMARY KEY,
        payload_json TEXT NOT NULL,
        updated_at INTEGER NOT NULL
      )
    ''');

    await db.execute('''
      CREATE TABLE IF NOT EXISTS pending_mutations(
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        store_id INTEGER NOT NULL DEFAULT 0,
        method TEXT NOT NULL,
        path TEXT NOT NULL,
        payload_json TEXT NOT NULL,
        label TEXT NOT NULL DEFAULT '',
        status TEXT NOT NULL DEFAULT 'pending',
        retry_count INTEGER NOT NULL DEFAULT 0,
        next_retry_at INTEGER NOT NULL DEFAULT 0,
        last_error TEXT NOT NULL DEFAULT '',
        created_at INTEGER NOT NULL
      )
    ''');
    await db.execute(
        'CREATE INDEX IF NOT EXISTS idx_mutations_status '
        'ON pending_mutations(status, next_retry_at)');

    // Bill total + owning store on the bill outbox row: the total lets the
    // Orders screen show a queued bill, and `store_id` guarantees a tablet
    // that is signed out and back in as ANOTHER store never uploads the
    // previous store's queued work.
    final columns = await db.rawQuery('PRAGMA table_info(pending_orders)');
    final hasTotal = columns.any((c) => c['name'] == 'total_amount');
    if (!hasTotal) {
      await db.execute(
          'ALTER TABLE pending_orders ADD COLUMN total_amount REAL NOT NULL DEFAULT 0');
    }
    final hasStore = columns.any((c) => c['name'] == 'store_id');
    if (!hasStore) {
      await db.execute(
          'ALTER TABLE pending_orders ADD COLUMN store_id INTEGER NOT NULL DEFAULT 0');
    }

    final mutationColumns =
        await db.rawQuery('PRAGMA table_info(pending_mutations)');
    final mutationsHaveStore =
        mutationColumns.any((c) => c['name'] == 'store_id');
    if (!mutationsHaveStore) {
      await db.execute(
          'ALTER TABLE pending_mutations ADD COLUMN store_id INTEGER NOT NULL DEFAULT 0');
    }
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
