// BizBite offline-gateway contract tests.
//
// These run WITHOUT sqflite: they only exercise branches where the gateway
// fails fast (network down + no cache, non-network server answers) so no
// platform channel is ever opened.
import 'package:flutter_test/flutter_test.dart';

import 'package:bizbite/core/network/api_exception.dart';
import 'package:bizbite/core/sync/offline_gateway.dart';
import 'package:bizbite/core/sync/offline_sources.dart';

/// A [OfflineStatusSink] double that records every call — proves repositories
/// talk to the UI contract without touching sqflite.
class FakeSink with OfflineStatusSink {
  final List<String> cached = [];
  final List<String> fresh = [];
  int queued = 0;

  @override
  void markQueued() => queued++;

  @override
  void markFresh(String source) => fresh.add(source);

  @override
  void markServedFromCache(String source) => cached.add(source);
}

ApiException networkDown() => ApiException(
      message: 'down',
      type: ApiExceptionType.network,
    );

void main() {
  test(
      'network failure with no cache resurfaces the original error',
      () async {
    final gateway = OfflineGateway(statusSink: FakeSink());
    await expectLater(
      gateway.readRaw(
        key: 'queue.all.any',
        source: OfflineSources.queue,
        fetch: () async => throw networkDown(),
      ),
      throwsA(
        isA<ApiException>().having(
          (e) => e.type,
          'type',
          ApiExceptionType.network,
        ),
      ),
    );
  });

  test('successful fetch marks the screen fresh', () async {
    final sink = FakeSink();
    final gateway = OfflineGateway(statusSink: sink);
    final read = await gateway.readRaw(
      key: 'queue.all.any',
      source: OfflineSources.queue,
      fetch: () async => <String, dynamic>{'orders': []},
    );
    expect(read.fromCache, isFalse);
    expect(read.data, {'orders': []});
    expect(sink.fresh, contains(OfflineSources.queue));
  });

  test('non-network failures are never hidden behind the cache', () async {
    final gateway = OfflineGateway(statusSink: FakeSink());
    await expectLater(
      gateway.readRaw(
        key: 'queue.all.any',
        source: OfflineSources.queue,
        fetch: () async => throw ApiException.forbidden(),
      ),
      throwsA(
        isA<ApiException>().having(
          (e) => e.type,
          'type',
          ApiExceptionType.forbidden,
        ),
      ),
    );
  });

  test('real server answers (422) propagate instead of queueing', () async {
    final sink = FakeSink();
    final gateway = OfflineGateway(statusSink: sink);
    await expectLater(
      gateway.queueWhenOffline(
        method: 'PATCH',
        path: '/orders/1/status',
        data: const {'status': 'ready'},
        label: 'Order status',
        send: () async => throw ApiException.validation(message: 'bad'),
      ),
      throwsA(
        isA<ApiException>().having(
          (e) => e.type,
          'type',
          ApiExceptionType.validation,
        ),
      ),
    );
    expect(sink.queued, 0);
  });
}
