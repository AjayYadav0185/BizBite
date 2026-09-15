// Push-then-pull sync engine: replays the `pending_orders` outbox FIFO,
// then refreshes the menu cache. Safe to call from anywhere — re-entrant
// kicks collapse into the in-flight run, and claims are transactional so a
// connectivity burst + manual retry can never double-post a bill.
//
// Conflict policy (see blueprint):
//   orders  -> append-only events, idempotent replay (server recomputes ₹)
//   catalog -> server-wins LWW (this pull overwrites local cache wholesale)
//   stock   -> operation deltas (server decrements; client never sends stock)
import 'dart:async';
import 'dart:convert';

import 'package:dio/dio.dart';

import '../../features/menu/data/models/category_model.dart';
import '../../features/menu/data/models/food_item_model.dart';
import '../../features/menu/data/models/menu_response_model.dart';
import '../config/api_config.dart';
import '../network/api_exception.dart';
import '../network/dio_client.dart';
import '../utils/parse_utils.dart';
import 'cache_dao.dart';
import 'cache_keys.dart';
import 'menu_cache_dao.dart';
import 'mutation_outbox_dao.dart';
import 'offline_sources.dart';
import 'outbox_dao.dart';
import 'sync_controller.dart';

class SyncResult {
  const SyncResult({
    required this.synced,
    required this.failed,
    required this.pending,
    this.menuRefreshed = false,
    this.mutationsSynced = 0,
    this.mutationsFailed = 0,
    this.cachesRefreshed = 0,
    this.deferred = false,
  });

  final int synced;
  final int failed;
  final int pending;
  final bool menuRefreshed;

  /// Non-bill writes replayed in this pass.
  final int mutationsSynced;

  /// Non-bill writes parked as `failed` in this pass.
  final int mutationsFailed;

  /// Screen caches refreshed after a clean push.
  final int cachesRefreshed;

  /// True when the pass stopped early (offline / 5xx / expired session) and
  /// the pulls were skipped to protect optimistic local state.
  final bool deferred;
}

class SyncOrchestrator {
  SyncOrchestrator({
    required this.client,
    required this.outbox,
    required this.menuCache,
    required this.sync,
    MutationOutboxDao? mutations,
    CacheDao? cache,
    int Function()? storeIdProvider,
  })  : mutations = mutations ?? MutationOutboxDao(),
        cache = cache ?? CacheDao(),
        _storeId = storeIdProvider;

  final DioClient client;
  final OutboxDao outbox;
  final MenuCacheDao menuCache;
  final SyncController sync;

  /// Outbox for every non-bill write (status moves, shifts, console, menu).
  final MutationOutboxDao mutations;

  /// Raw-envelope cache for every screen read.
  final CacheDao cache;

  /// Only work queued by the currently signed-in store is replayed.
  final int Function()? _storeId;

  int get _activeStoreId => _storeId?.call() ?? 0;

  bool _running = false;
  Completer<SyncResult>? _inflight;

  static SyncOrchestrator? _instance;
  static SyncOrchestrator get instance => _instance!;
  static void register(SyncOrchestrator orchestrator) =>
      _instance = orchestrator;

  /// Coalescing entry-point: concurrent kicks share one run.
  Future<SyncResult> kick() {
    final inflight = _inflight;
    if (inflight != null) return inflight.future;
    final completer = Completer<SyncResult>();
    _inflight = completer;
    unawaited(_run(completer));
    return completer.future;
  }

  Future<void> _run(Completer<SyncResult> completer) async {
    if (_running) {
      completer.complete(const SyncResult(synced: 0, failed: 0, pending: 0));
      _inflight = null;
      return;
    }
    _running = true;
    sync.setSyncing(true);
    var synced = 0;
    var mutationsSynced = 0;
    var mutationsFailed = 0;
    var deferred = false;
    try {
      // -- PUSH 1/2: replay the BILL outbox FIFO -----------------------
      final batch = await outbox.claimDueBatch(storeId: _activeStoreId);
      await sync.probe();
      for (final op in batch) {
        try {
          final payload =
              jsonDecode(op.payloadJson) as Map<String, dynamic>;
          await client.post<dynamic>(ApiConfig.orders, data: payload);
          await outbox.markSynced(op.id);
          synced++;
        } on DioException catch (error) {
          final status = error.response?.statusCode;
          if (status == 422 || status == 409 || status == 404) {
            // Poison row (item deleted / made unavailable mid-offline):
            // park for manager review, never infinite-retry.
            final message = _serverMessage(error) ??
                'Bill ${op.localBillNo} rejected by server.';
            await outbox.markFailed(op.id, message);
            sync.setLastError(message);
          } else if (status == 401 || status == 403) {
            // Session/role problem: release claim, surface, stop batch.
            await outbox.scheduleRetry(op.id, op.retryCount);
            sync.setLastError(
              'Sync paused — session expired. Sign in again.',
            );
            deferred = true;
            break;
          } else {
            // Network/timeout/5xx: backoff and stop this batch.
            await outbox.scheduleRetry(op.id, op.retryCount);
            deferred = true;
            break;
          }
        } catch (error) {
          await outbox.scheduleRetry(op.id, op.retryCount);
          sync.setLastError(apiExceptionFrom(error).message);
          deferred = true;
          break;
        }
      }

      // -- PUSH 2/2: replay the NON-BILL mutation outbox ---------------
      if (!deferred) {
        final replay = await _replayMutations();
        mutationsSynced = replay.synced;
        mutationsFailed = replay.failed;
        deferred = replay.deferred;
      }

      // -- PULL: refresh the menu + every screen cache (server-wins) ---
      // Skipped whenever the push stopped early: pulling now would erase the
      // optimistic local edits still waiting in the outbox.
      var menuRefreshed = false;
      var cachesRefreshed = 0;
      if (!deferred) {
        menuRefreshed = await _pullMenu();
        cachesRefreshed = await _pullScreenCaches();
        if (menuRefreshed) sync.markSynced();
      }

      final pending = await outbox.pendingCount(storeId: _activeStoreId);
      final pendingMutations =
          await mutations.pendingCount(storeId: _activeStoreId);
      sync.setPendingCount(pending);
      sync.setPendingMutationCount(pendingMutations);
      if (pending == 0 && pendingMutations == 0 && menuRefreshed) {
        sync.setLastError('');
      }
      completer.complete(
        SyncResult(
          synced: synced,
          failed: (await outbox.failed(storeId: _activeStoreId)).length,
          pending: pending,
          menuRefreshed: menuRefreshed,
          mutationsSynced: mutationsSynced,
          mutationsFailed: mutationsFailed,
          cachesRefreshed: cachesRefreshed,
          deferred: deferred,
        ),
      );
    } catch (error) {
      // Never let kick()'s future complete with an error: it is fired
      // unawaited from connectivity listeners and the post-frame boot hook,
      // so a DB hiccup would surface as an unhandled async exception.
      // The banner already carries the failure via [sync.setLastError].
      sync.setLastError(apiExceptionFrom(error).message);
      if (!completer.isCompleted) {
        completer.complete(
          SyncResult(
            synced: synced,
            failed: 0,
            pending: 0,
            mutationsSynced: mutationsSynced,
            mutationsFailed: mutationsFailed,
            deferred: true,
          ),
        );
      }
    } finally {
      _running = false;
      _inflight = null;
      sync.setSyncing(false);
      unawaited(sync.refreshQueued());
    }
  }

  /// Replay queued non-bill writes FIFO (order status, shifts, console, menu).
  ///
  /// Every queued verb is idempotent, so a replay after a timeout the server
  /// actually processed is harmless. Poison rows (422/404/409) are parked as
  /// `failed` for the manager; auth/network problems stop the batch and defer
  /// the pulls so optimistic local state is not overwritten.
  Future<({int synced, int failed, bool deferred})> _replayMutations() async {
    var synced = 0;
    var failed = 0;
    var deferred = false;

    final batch = await mutations.claimDueBatch(storeId: _activeStoreId);
    for (final op in batch) {
      try {
        final body = jsonDecode(op.payloadJson);
        await _sendMutation(op.method, op.path, body);
        await mutations.markSynced(op.id);
        synced++;
      } on DioException catch (error) {
        final status = error.response?.statusCode;
        if (status == 422 || status == 409 || status == 404) {
          final message =
              _serverMessage(error) ?? '${op.label} was rejected by the server.';
          await mutations.markFailed(op.id, message);
          sync.setLastError(message);
          failed++;
        } else if (status == 401 || status == 403) {
          await mutations.release(op.id);
          sync.setLastError('Sync paused — session expired. Sign in again.');
          deferred = true;
          break;
        } else {
          await mutations.scheduleRetry(op.id, op.retryCount);
          deferred = true;
          break;
        }
      } catch (error) {
        await mutations.scheduleRetry(op.id, op.retryCount);
        sync.setLastError(apiExceptionFrom(error).message);
        deferred = true;
        break;
      }
    }
    return (synced: synced, failed: failed, deferred: deferred);
  }

  /// Replay one queued verb on the shared [DioClient].
  Future<void> _sendMutation(String method, String path, Object? body) async {
    final data = body is Map ? body : null;
    switch (method.toUpperCase()) {
      case 'POST':
        await client.post<dynamic>(path, data: data);
      case 'PUT':
        await client.put<dynamic>(path, data: data);
      case 'DELETE':
        await client.delete<dynamic>(path, data: data);
      default:
        await client.patch<dynamic>(path, data: data);
    }
  }

  /// Refresh the menu cache (catalog is server-wins LWW).
  Future<bool> _pullMenu() async {
    try {
      final response = await client.get<dynamic>(ApiConfig.menu);
      final menu = MenuResponseModel.fromJson(toMap(response.data));
      await menuCache.replaceAll(
        categories: menu.categories.isEmpty
            ? await menuCache.readCategories()
            : menu.categories,
        items: menu.items,
      );
      return true;
    } on DioException {
      // Offline pull is expected — keep serving the last-good cache.
      return false;
    }
  }

  /// Refresh the raw envelope cache behind every list screen.
  ///
  /// Reports are omitted on purpose: they are per-date/range and each report
  /// screen refreshes its own cache when it opens online.
  Future<int> _pullScreenCaches() async {
    final targets = <({
      String key,
      String source,
      String path,
      Map<String, dynamic>? query,
    })>[
      (
        key: CacheKeys.queue(),
        source: OfflineSources.queue,
        path: ApiConfig.orders,
        query: null,
      ),
      (
        key: CacheKeys.queue(openOnly: true),
        source: OfflineSources.queue,
        path: ApiConfig.orders,
        query: {'status': 'open'},
      ),
      (
        key: CacheKeys.shifts,
        source: OfflineSources.shifts,
        path: ApiConfig.shifts,
        query: null,
      ),
      (
        key: CacheKeys.tables,
        source: OfflineSources.tables,
        path: ApiConfig.tables,
        query: null,
      ),
      (
        key: CacheKeys.campaigns,
        source: OfflineSources.campaigns,
        path: ApiConfig.campaigns,
        query: null,
      ),
      (
        key: CacheKeys.staff,
        source: OfflineSources.staff,
        path: ApiConfig.staff,
        query: null,
      ),
      (
        key: CacheKeys.wallet,
        source: OfflineSources.wallet,
        path: ApiConfig.walletBalance,
        query: null,
      ),
    ];

    var refreshed = 0;
    for (final target in targets) {
      try {
        final response = await client.get<dynamic>(
          target.path,
          query: target.query,
        );
        await cache.put(target.key, response.data);
        sync.markFresh(target.source);
        refreshed++;
      } catch (_) {
        // 403 (cashier vs staff list), offline, or a slow endpoint: keep the
        // last-good cache for that screen and never fail the whole pass.
      }
    }
    return refreshed;
  }

  String? _serverMessage(DioException error) {
    final data = error.response?.data;
    if (data is Map<String, dynamic>) {
      final message = data['message'];
      if (message is String && message.trim().isNotEmpty) {
        return message.trim();
      }
    }
    return null;
  }

  /// Parse helper kept local so sync never depends on MenuRepository shape.
  List<CategoryModel> parseCategories(dynamic raw) =>
      [for (final m in toListOfMaps(raw)) CategoryModel.fromJson(m)];

  List<FoodItemModel> parseItems(dynamic raw) =>
      [for (final m in toListOfMaps(raw)) FoodItemModel.fromJson(m)];
}
