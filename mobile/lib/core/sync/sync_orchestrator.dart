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
import 'menu_cache_dao.dart';
import 'outbox_dao.dart';
import 'sync_controller.dart';

class SyncResult {
  const SyncResult({
    required this.synced,
    required this.failed,
    required this.pending,
    this.menuRefreshed = false,
  });

  final int synced;
  final int failed;
  final int pending;
  final bool menuRefreshed;
}

class SyncOrchestrator {
  SyncOrchestrator({
    required this.client,
    required this.outbox,
    required this.menuCache,
    required this.sync,
  });

  final DioClient client;
  final OutboxDao outbox;
  final MenuCacheDao menuCache;
  final SyncController sync;

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
    try {
      // -- PUSH: replay outbox FIFO ------------------------------------
      final batch = await outbox.claimDueBatch();
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
            break;
          } else {
            // Network/timeout/5xx: backoff and stop this batch.
            await outbox.scheduleRetry(op.id, op.retryCount);
            break;
          }
        } catch (error) {
          await outbox.scheduleRetry(op.id, op.retryCount);
          sync.setLastError(apiExceptionFrom(error).message);
          break;
        }
      }

      // -- PULL: refresh menu cache (catalog is server-wins) -----------
      var menuRefreshed = false;
      try {
        final response = await client.get<dynamic>(ApiConfig.menu);
        final menu = MenuResponseModel.fromJson(toMap(response.data));
        await menuCache.replaceAll(
          categories: menu.categories.isEmpty
              ? await menuCache.readCategories()
              : menu.categories,
          items: menu.items,
        );
        menuRefreshed = true;
        sync.markSynced();
      } on DioException {
        // Offline pull is expected — keep serving last-good cache.
      }

      final pending = await outbox.pendingCount();
      sync.setPendingCount(pending);
      if (pending == 0 && menuRefreshed) sync.setLastError('');
      completer.complete(
        SyncResult(
          synced: synced,
          failed: (await outbox.failed()).length,
          pending: pending,
          menuRefreshed: menuRefreshed,
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
          SyncResult(synced: synced, failed: 0, pending: 0),
        );
      }
    } finally {
      _running = false;
      _inflight = null;
      sync.setSyncing(false);
      unawaited(outbox.pendingCount().then(sync.setPendingCount));
    }
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
