// The single offline-first gateway used by every repository.
//
// Two primitives:
//
//   readRaw()         network-first read; on a NETWORK failure it replays the
//                     last cached payload from SQLite (`cache_entries`) and
//                     flags the screen as "cached" through [OfflineStatusSink].
//                     Non-network failures (401/403/422) are real answers and
//                     are re-thrown untouched.
//
//   queueWhenOffline() runs a write; when the link is dead it parks the exact
//                     verb + path + body in `pending_mutations` so
//                     `SyncOrchestrator` can replay it later, and reports
//                     "queued" so the caller can apply the optimistic local
//                     patch + tell the cashier it was saved.
//
// Repository-level, not widget-level: screens keep rendering whatever the
// repository returns, online or offline.
import 'dart:convert';

import '../network/api_exception.dart';
import 'cache_dao.dart';
import 'mutation_outbox_dao.dart';
import 'offline_sources.dart';

/// Result of a network-first read.
class RawRead {
  const RawRead({this.data, this.fromCache = false, this.cachedAt});

  /// Raw API payload (`response.data`) — the SAME envelope the server sent,
  /// so callers parse it with one code path online and offline.
  final Object? data;

  /// True when [data] came from SQLite because the server was unreachable.
  final bool fromCache;

  /// Age marker of the cached payload (null for fresh/network reads).
  final DateTime? cachedAt;
}

class OfflineGateway {
  OfflineGateway({
    CacheDao? cache,
    MutationOutboxDao? mutations,
    OfflineStatusSink? statusSink,
    int Function()? storeIdProvider,
  })  : cache = cache ?? CacheDao(),
        mutations = mutations ?? MutationOutboxDao(),
        _status = statusSink,
        _storeId = storeIdProvider;

  /// Exposed so repositories can apply optimistic patches / bootstraps.
  final CacheDao cache;
  final MutationOutboxDao mutations;

  final int Function()? _storeId;

  /// Store that owns this device session — stamped on every queued row so a
  /// tablet signed into another store never uploads foreign work (`0` until
  /// the session profile is restored).
  int get activeStoreId => _storeId?.call() ?? 0;

  OfflineStatusSink? _status;

  /// Wired after construction when the sink is created later in `app.dart`.
  set statusSink(OfflineStatusSink? sink) => _status = sink;

  /// Lets repositories that manage their OWN cache (the menu) report the same
  /// online/cached state the raw-envelope reads report automatically.
  void markFresh(String source) => _status?.markFresh(source);

  /// See [markFresh].
  void markServedFromCache(String source) =>
      _status?.markServedFromCache(source);

  // ------------------------------------------------------------------
  // Reads
  // ------------------------------------------------------------------

  /// Network-first read with SQLite fallback.
  ///
  /// [fetch] must throw an [ApiException] (repositories convert their
  /// DioException via `apiExceptionFrom`). Network failures fall back to the
  /// cached envelope; anything else is a real server answer and propagates.
  Future<RawRead> readRaw({
    required String key,
    required String source,
    required Future<Object?> Function() fetch,
  }) async {
    try {
      final data = await fetch();
      // Best-effort cache write: a sqflite hiccup must never fail a read the
      // caller already has in hand.
      try {
        await cache.put(key, data);
      } catch (_) {}
      _status?.markFresh(source);
      return RawRead(data: data);
    } on ApiException catch (error) {
      if (!error.isNetworkError) rethrow;
      final fallback = await cached(key);
      if (fallback == null) rethrow;
      _status?.markServedFromCache(source);
      return fallback;
    }
  }

  /// Cached payload for [key], or null when it was never cached / unreadable.
  Future<RawRead?> cached(String key) async {
    try {
      final data = await cache.read(key);
      if (data == null) return null;
      return RawRead(
        data: data,
        fromCache: true,
        cachedAt: await cache.updatedAt(key),
      );
    } catch (_) {
      // Corrupt/unreadable cache behaves exactly like a cache miss.
      return null;
    }
  }

  // ------------------------------------------------------------------
  // Writes
  // ------------------------------------------------------------------

  /// Run [send]; if the device cannot reach the server, queue the SAME
  /// request for `SyncOrchestrator` and return true.
  ///
  /// Returns false when [send] succeeded online. Any non-network failure
  /// (validation, forbidden, not found) propagates — those need the cashier.
  Future<bool> queueWhenOffline({
    required String method,
    required String path,
    required Map<String, dynamic>? data,
    required String label,
    required Future<void> Function() send,
  }) async {
    try {
      await send();
      return false;
    } on ApiException catch (error) {
      if (!error.isNetworkError) rethrow;
      await mutations.enqueue(
        method: method,
        path: path,
        payloadJson: jsonEncode(data),
        label: label,
        storeId: activeStoreId,
      );
      _status?.markQueued();
      return true;
    }
  }

  /// True when [error] means "the server was unreachable" — the only class of
  /// failure the POS hides behind cached data / a queued write.
  static bool isOffline(Object error) =>
      error is ApiException && error.isNetworkError;
}