// BizBite offline-cache regression tests.
//
// Pure-Dart tests (no sqflite platform channel): they pin the rules every
// screen's offline behaviour depends on — cache-key shaping (a filter change
// must never read another filter's cache), optimistic list patches (a queued
// write must show up immediately) and the mutation row model.
import 'package:flutter_test/flutter_test.dart';

import 'package:bizbite/core/sync/cache_keys.dart';
import 'package:bizbite/core/sync/cache_patch.dart';
import 'package:bizbite/core/sync/offline_queued_exception.dart';
import 'package:bizbite/core/sync/offline_sources.dart';
import 'package:bizbite/core/sync/pending_mutation.dart';

void main() {
  group('CacheKeys', () {
    test('queue keys split every filter combination', () {
      expect(CacheKeys.queue(), 'queue.all.any');
      expect(CacheKeys.queue(openOnly: true), 'queue.open.any');
      expect(
        CacheKeys.queue(openOnly: true, type: 'dine_in'),
        'queue.open.dine_in',
      );
      expect(CacheKeys.queue(type: 'delivery'), 'queue.all.delivery');

      // A filter change must never read another filter's cache.
      expect(CacheKeys.queue(type: 'parcel') == CacheKeys.queue(), isFalse);
    });

    test('report keys carry their date range', () {
      expect(CacheKeys.hourly('2026-09-14'), 'report.hourly.2026-09-14');
      expect(
        CacheKeys.bestSellers('2026-09-14', '2026-09-15'),
        'report.best.2026-09-14.2026-09-15',
      );
      expect(
        CacheKeys.range('2026-09-14', '2026-09-15'),
        'report.range.2026-09-14.2026-09-15',
      );
    });

    test('offline sources name every cache the UI can flag', () {
      expect(
        {
          OfflineSources.menu,
          OfflineSources.queue,
          OfflineSources.shifts,
          OfflineSources.reports,
          OfflineSources.tables,
          OfflineSources.campaigns,
          OfflineSources.staff,
          OfflineSources.wallet,
        }.length,
        8,
      );
    });
  });

  group('patchCacheList', () {
    List<Object?> rows() => const [
          {'id': 1, 'status': 'pending'},
          {'id': 2, 'status': 'preparing'},
        ];

    test('merges the patch into the first matching row only', () {
      final result = patchCacheList(
        rows(),
        match: (row) => row['id'] == 1,
        patch: {'status': 'ready'},
      );
      expect(result.changed, isTrue);
      final row = cacheRow(result.rows.first);
      expect(row['status'], 'ready');
      expect(cacheRow(result.rows[1])['status'], 'preparing');
    });

    test('removes the row when asked', () {
      final result = patchCacheList(
        rows(),
        match: (row) => row['id'] == 2,
        remove: true,
      );
      expect(result.changed, isTrue);
      expect(result.rows, hasLength(1));
      expect(cacheRow(result.rows.single)['id'], 1);
    });

    test('a miss returns the identical list (no wasted SQLite write)', () {
      final input = rows();
      final result = patchCacheList(
        input,
        match: (row) => row['id'] == 999,
        patch: {'status': 'ready'},
      );
      expect(result.changed, isFalse);
      expect(identical(result.rows, input), isTrue);
    });

    test('cacheRow coerces dynamic JSON maps safely', () {
      expect(cacheRow({'a': 1}), {'a': 1});
      expect(cacheRow(const []), isEmpty);
      expect(cacheRow(null), isEmpty);
    });
  });

  group('cache envelopes', () {
    test('nested lists are found and rebuilt under their key', () {
      const envelope = {
        'orders': [
          {'id': 1},
        ],
      };
      final list = cacheListAt(envelope, 'orders');
      expect(list, hasLength(1));

      final rebuilt = cacheEnvelopeWith(envelope, 'orders', const []);
      expect(rebuilt, {'orders': []});
    });

    test('a shape mismatch yields null instead of crashing the sync', () {
      expect(cacheListAt(const {'orders': 'nope'}, 'orders'), isNull);
      expect(cacheListAt(const [1, 2, 3], 'orders'), isNull);
      expect(cacheListAt('garbage', 'orders'), isNull);
    });

    test('root-level lists pass through unwrapped', () {
      const items = [
        {'id': 1}
      ];
      expect(cacheListAt(items, ''), items);
      expect(cacheEnvelopeWith(items, '', const []), isEmpty);
    });
  });

  group('PendingMutation', () {
    test('parses the outbox row with safe defaults', () {
      const row = PendingMutation(
        id: 3,
        method: 'PATCH',
        path: '/orders/41/status',
        payloadJson: '{"status":"ready"}',
        label: 'Order status',
        status: 'pending',
        retryCount: 0,
        nextRetryAt: 0,
        lastError: '',
        createdAt: 1700000000000,
      );
      expect(row.method, 'PATCH');
      expect(row.isDue, isTrue);

      final fromMap = PendingMutation.fromMap(const {'id': 7});
      expect(fromMap.method, 'PATCH');
      expect(fromMap.path, '');
      expect(fromMap.payloadJson, 'null');
      expect(fromMap.retryCount, 0);
    });
  });

  group('OfflineQueuedException', () {
    test('bills carry the LOCAL identity, changes carry the label', () {
      final bill = OfflineQueuedException(
        localBillNo: 'LOCAL-A1B2C3D4',
        idempotencyKey: 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
      );
      expect(bill.isBill, isTrue);
      expect('$bill', contains('LOCAL-A1B2C3D4'));
      expect(bill.message, contains('saved offline'));

      final change = OfflineQueuedException(label: 'Shift open');
      expect(change.isBill, isFalse);
      expect(change.message, contains('Shift open'));
    });
  });
}
