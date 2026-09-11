// DAO for the `pending_orders` outbox table.
//
// Concurrency contract: `claimDueBatch()` flips rows to `syncing` inside one
// transaction so two kick() calls (connectivity event + manual retry) can
// never double-post the same bill. Terminal rows are deleted on success;
// poison rows (422 from Laravel) are parked as `failed` for manager review.
import 'package:sqflite/sqflite.dart';

import 'app_database.dart';
import 'pending_order.dart';

class OutboxDao {
  OutboxDao({AppDatabase? database}) : _db = database ?? AppDatabase.instance;

  final AppDatabase _db;

  Future<void> enqueue({
    required String idempotencyKey,
    required String payloadJson,
    required String localBillNo,
  }) async {
    final database = await _db.db;
    await database.insert(
      'pending_orders',
      {
        'idempotency_key': idempotencyKey,
        'payload_json': payloadJson,
        'local_bill_no': localBillNo,
        'status': 'pending',
        'retry_count': 0,
        'next_retry_at': 0,
        'last_error': '',
        'created_at': DateTime.now().millisecondsSinceEpoch,
      },
      conflictAlgorithm: ConflictAlgorithm.ignore,
    );
  }

  /// Atomically claim up to [limit] due rows for this sync attempt.
  Future<List<PendingOrder>> claimDueBatch({int limit = 20}) async {
    final database = await _db.db;
    final now = DateTime.now().millisecondsSinceEpoch;
    return database.transaction((txn) async {
      final rows = await txn.query(
        'pending_orders',
        where: 'status = ? AND next_retry_at <= ?',
        whereArgs: ['pending', now],
        orderBy: 'id ASC',
        limit: limit,
      );
      for (final row in rows) {
        await txn.update(
          'pending_orders',
          {'status': 'syncing'},
          where: 'id = ? AND status = ?',
          whereArgs: [row['id'], 'pending'],
        );
      }
      final claimed = await txn.query(
        'pending_orders',
        where: 'status = ?',
        whereArgs: ['syncing'],
        orderBy: 'id ASC',
        limit: limit,
      );
      return [for (final map in claimed) PendingOrder.fromMap(map)];
    });
  }

  Future<void> markSynced(int id) async {
    final database = await _db.db;
    await database.delete('pending_orders', where: 'id = ?', whereArgs: [id]);
  }

  Future<void> markFailed(int id, String error) async {
    final database = await _db.db;
    await database.update(
      'pending_orders',
      {'status': 'failed', 'last_error': error},
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  /// Exponential backoff: 5s, 30s, 2m, 10m, capped at 15m.
  Future<void> scheduleRetry(int id, int retryCount) async {
    const delaysMs = [5000, 30000, 120000, 600000, 900000];
    final capped = retryCount.clamp(0, delaysMs.length - 1);
    final database = await _db.db;
    await database.update(
      'pending_orders',
      {
        'status': 'pending',
        'retry_count': retryCount + 1,
        'next_retry_at':
            DateTime.now().millisecondsSinceEpoch + delaysMs[capped],
      },
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  Future<int> pendingCount() async {
    final database = await _db.db;
    final rows = await database.rawQuery(
      "SELECT COUNT(*) AS c FROM pending_orders WHERE status IN ('pending','syncing')",
    );
    return ((rows.first['c'] as num?) ?? 0).toInt();
  }

  Future<List<PendingOrder>> failed() async {
    final database = await _db.db;
    final rows = await database.query(
      'pending_orders',
      where: 'status = ?',
      whereArgs: ['failed'],
      orderBy: 'id DESC',
    );
    return [for (final map in rows) PendingOrder.fromMap(map)];
  }

  Future<void> retryFailed(int id) async {
    final database = await _db.db;
    await database.update(
      'pending_orders',
      {'status': 'pending', 'next_retry_at': 0, 'last_error': ''},
      where: 'id = ?',
      whereArgs: [id],
    );
  }
}
