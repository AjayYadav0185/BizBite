// Generic SQLite cache for every screen's last-good server payload.
//
// One row per endpoint (+ filter combo). The value is the RAW API envelope
// (`{'orders': [...]}`, `{'tables': [...]}`, …) so repositories can hand it
// back to the exact same parser they use online — zero schema drift between
// the online and offline rendering paths.
//
// Writes are best-effort: a cache failure must never turn a successful
// network read into an error (the UI already has the fresh data).
import 'dart:convert';

import 'package:sqflite/sqflite.dart';

import 'app_database.dart';
import 'cache_keys.dart';
import 'cache_patch.dart';

class CacheDao {
  CacheDao({AppDatabase? database}) : _db = database ?? AppDatabase.instance;

  final AppDatabase _db;

  // ------------------------------------------------------------------
  // Read / write
  // ------------------------------------------------------------------

  Future<void> put(String key, Object? value) async {
    final database = await _db.db;
    await database.insert(
      'cache_entries',
      {
        'key': key,
        'payload_json': jsonEncode(value),
        'updated_at': DateTime.now().millisecondsSinceEpoch,
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  /// Decoded payload, or null when the key was never cached.
  Future<Object?> read(String key) async {
    final database = await _db.db;
    final rows = await database.query(
      'cache_entries',
      where: 'key = ?',
      whereArgs: [key],
      limit: 1,
    );
    if (rows.isEmpty) return null;
    return _decode(rows.first['payload_json']);
  }

  Future<DateTime?> updatedAt(String key) async {
    final database = await _db.db;
    final rows = await database.query(
      'cache_entries',
      columns: ['updated_at'],
      where: 'key = ?',
      whereArgs: [key],
      limit: 1,
    );
    if (rows.isEmpty) return null;
    final ms = (rows.first['updated_at'] as num?)?.toInt();
    if (ms == null || ms <= 0) return null;
    return DateTime.fromMillisecondsSinceEpoch(ms);
  }

  Future<bool> has(String key) async {
    final database = await _db.db;
    final rows = await database.rawQuery(
      'SELECT 1 FROM cache_entries WHERE key = ? LIMIT 1',
      [key],
    );
    return rows.isNotEmpty;
  }

  // ------------------------------------------------------------------
  // Optimistic patches (queued offline writes)
  // ------------------------------------------------------------------

  /// Patch the list at [listKey] inside EVERY cached entry under [prefix].
  ///
  /// Used right after a mutation is queued so the grid/list already reflects
  /// the change. The next successful server pull overwrites the envelope
  /// wholesale (server-wins LWW), so a wrong guess self-heals.
  Future<int> patchWhere({
    required String prefix,
    required bool Function(Map<String, dynamic> row) match,
    Map<String, dynamic>? patch,
    bool remove = false,
    String listKey = '',
  }) async {
    final database = await _db.db;
    final rows = await database.query(
      'cache_entries',
      where: 'key LIKE ?',
      whereArgs: ['$prefix%'],
    );

    var touched = 0;
    for (final row in rows) {
      final key = row['key'].toString();
      final decoded = _decode(row['payload_json']);
      final list = cacheListAt(decoded, listKey);
      if (list == null) continue;

      final result = patchCacheList(
        list,
        match: match,
        patch: patch,
        remove: remove,
      );
      if (!result.changed) continue;

      await put(key, cacheEnvelopeWith(decoded, listKey, result.rows));
      touched++;
    }
    return touched;
  }

  /// Insert-or-replace a row inside every cached envelope under [prefix]
  /// (used for locally-created rows that carry a negative placeholder id).
  Future<int> upsertWhere({
    required String prefix,
    required bool Function(Map<String, dynamic> row) match,
    required Map<String, dynamic> row,
    String listKey = '',
  }) async {
    final database = await _db.db;
    final rows = await database.query(
      'cache_entries',
      where: 'key LIKE ?',
      whereArgs: ['$prefix%'],
    );

    var touched = 0;
    for (final entry in rows) {
      final key = entry['key'].toString();
      final decoded = _decode(entry['payload_json']);
      final list = cacheListAt(decoded, listKey);
      if (list == null) continue;

      final existing = patchCacheList(list, match: match, patch: row);
      final next = existing.changed
          ? existing.rows
          : <Object?>[...list, row];
      await put(key, cacheEnvelopeWith(decoded, listKey, next));
      touched++;
    }
    return touched;
  }

  // ------------------------------------------------------------------
  // Housekeeping
  // ------------------------------------------------------------------

  Future<int> delete(String key) async {
    final database = await _db.db;
    return database.delete('cache_entries', where: 'key = ?', whereArgs: [key]);
  }

  Future<int> deletePrefix(String prefix) async {
    final database = await _db.db;
    return database.delete(
      'cache_entries',
      where: 'key LIKE ?',
      whereArgs: ['$prefix%'],
    );
  }

  /// Sign-out hygiene: drop every cached payload for the previous store.
  Future<void> clearAll() async {
    for (final key in CacheKeys.all) {
      await delete(key);
    }
    for (final prefix in CacheKeys.prefixes) {
      await deletePrefix(prefix);
    }
  }

  Object? _decode(Object? raw) {
    if (raw is! String || raw.isEmpty) return null;
    try {
      return jsonDecode(raw);
    } catch (_) {
      return null;
    }
  }
}
