// DAO for the `pending_mutations` outbox (non-bill writes queued offline).
//
// Same concurrency contract as `OutboxDao`: `claimDueBatch()` flips rows to
// `syncing` inside ONE transaction, so a connectivity burst + a manual retry
// can never replay the same PATCH twice. Terminal rows are deleted on
// success; poison rows (422/404/409) are parked as `failed` for the manager.
import 'app_database.dart';
import 'pending_mutation.dart';
class MutationOutboxDao {
  MutationOutboxDao({AppDatabase? database})
      : _db = database ?? AppDatabase.instance;

  final AppDatabase _db;

  Future<void> enqueue({
    required String method,
    required String path,
    required String payloadJson,
    required String label,
    int storeId = 0,
  }) async {
    final database = await _db.db;
    await database.insert(
      'pending_mutations',
      {
        'store_id': storeId,
        'method': method.toUpperCase(),
        'path': path,
        'payload_json': payloadJson,
        'label': label,
        'status': 'pending',
        'retry_count': 0,
        'next_retry_at': 0,
        'last_error': '',
        'created_at': DateTime.now().millisecondsSinceEpoch,
      },
    );
  }

  /// Rows owned by [storeId] (or by an unknown store from a pre-v2 install).
  static const String _ownedBy = '(store_id = ? OR store_id = 0)';

  /// Atomically claim up to [limit] due rows for this sync attempt.
  Future<List<PendingMutation>> claimDueBatch({
    int limit = 20,
    int storeId = 0,
  }) async {
    final database = await _db.db;
    final now = DateTime.now().millisecondsSinceEpoch;
    return database.transaction((txn) async {
      final rows = await txn.query(
        'pending_mutations',
        where: 'status = ? AND next_retry_at <= ? AND $_ownedBy',
        whereArgs: ['pending', now, storeId],
        orderBy: 'id ASC',
        limit: limit,
      );
      for (final row in rows) {
        await txn.update(
          'pending_mutations',
          {'status': 'syncing'},
          where: 'id = ? AND status = ?',
          whereArgs: [row['id'], 'pending'],
        );
      }
      final claimed = await txn.query(
        'pending_mutations',
        where: 'status = ? AND $_ownedBy',
        whereArgs: ['syncing', storeId],
        orderBy: 'id ASC',
        limit: limit,
      );
      return [for (final map in claimed) PendingMutation.fromMap(map)];
    });
  }

  Future<void> markSynced(int id) async {
    final database = await _db.db;
    await database.delete('pending_mutations', where: 'id = ?', whereArgs: [id]);
  }

  Future<void> markFailed(int id, String error) async {
    final database = await _db.db;
    await database.update(
      'pending_mutations',
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
      'pending_mutations',
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

  /// Releases a `syncing` claim without counting a retry (session expired:
  /// the batch stops and the rows must stay pending for the next kick).
  Future<void> release(int id) async {
    final database = await _db.db;
    await database.update(
      'pending_mutations',
      {'status': 'pending'},
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  Future<int> pendingCount({int storeId = 0}) async {
    final database = await _db.db;
    final rows = await database.rawQuery(
      "SELECT COUNT(*) AS c FROM pending_mutations "
      "WHERE status IN ('pending','syncing') AND $_ownedBy",
      [storeId],
    );
    return ((rows.first['c'] as num?) ?? 0).toInt();
  }

  Future<List<PendingMutation>> failed({int storeId = 0}) async {
    final database = await _db.db;
    final rows = await database.query(
      'pending_mutations',
      where: 'status = ? AND $_ownedBy',
      whereArgs: ['failed', storeId],
      orderBy: 'id DESC',
    );
    return [for (final map in rows) PendingMutation.fromMap(map)];
  }

  Future<void> retryFailed(int id) async {
    final database = await _db.db;
    await database.update(
      'pending_mutations',
      {'status': 'pending', 'next_retry_at': 0, 'last_error': ''},
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  /// Sign-out hygiene: the queued writes belong to the signed-out store.
  Future<int> clearAll() async {
    final database = await _db.db;
    return database.delete('pending_mutations');
  }
}
