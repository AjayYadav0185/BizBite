// Offline-first connectivity + sync state for the status banner.
//
// Listens to `connectivity_plus` (Wi-Fi/mobile/none) AND probes the real
// backend (`HEAD /api/user`) because captive portals and dead routers lie
// about connectivity.
//
// It is also the app-wide [OfflineStatusSink]: repositories report through it
// when they serve SQLite data instead of fresh server data (`markServedFromCache`)
// and when a write was parked in an outbox (`markQueued`), so every screen can
// show an honest "Cached" chip and the banner can count queued work.
import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';

import '../network/dio_client.dart';
import 'mutation_outbox_dao.dart';
import 'offline_sources.dart';
import 'outbox_dao.dart';

enum SyncBannerState { online, offline, syncing, failed }

class SyncController extends ChangeNotifier with OfflineStatusSink {
  SyncController({
    required this._client,
    Connectivity? connectivity,
    OutboxDao? outbox,
    MutationOutboxDao? mutations,
    int Function()? storeIdProvider,
  })  : _connectivity = connectivity ?? Connectivity(),
        _outbox = outbox ?? OutboxDao(),
        _mutations = mutations ?? MutationOutboxDao(),
        _storeId = storeIdProvider;

  final DioClient _client;
  final Connectivity _connectivity;
  final OutboxDao _outbox;
  final MutationOutboxDao _mutations;

  /// Signed-in store — only its queued work is counted / replayed.
  final int Function()? _storeId;

  int get _activeStoreId => _storeId?.call() ?? 0;

  StreamSubscription<List<ConnectivityResult>>? _subscription;

  bool _online = true;
  bool _syncing = false;
  int _pendingCount = 0;
  int _pendingMutationCount = 0;
  String _lastError = '';
  DateTime? _lastSyncAt;

  /// Screens currently painting SQLite data instead of fresh server data.
  final Set<String> _staleSources = {};

  bool get isOnline => _online;
  bool get isSyncing => _syncing;

  /// Queued bills awaiting replay.
  int get pendingCount => _pendingCount;

  /// Queued non-bill writes (status moves, shifts, console, menu) awaiting replay.
  int get pendingMutationCount => _pendingMutationCount;

  /// Everything the banner should count as "not on the server yet".
  int get pendingTotal => _pendingCount + _pendingMutationCount;

  String get lastError => _lastError;
  DateTime? get lastSyncAt => _lastSyncAt;

  /// True when [source] is showing cached data (one of [OfflineSources]).
  bool isServedFromCache(String source) => _staleSources.contains(source);

  /// Any screen showing cached data — drives the "cached" hint in the banner.
  bool get hasStaleData => _staleSources.isNotEmpty;

  SyncBannerState get banner => !_online
      ? SyncBannerState.offline
      : _syncing
          ? SyncBannerState.syncing
          : _lastError.isNotEmpty
              ? SyncBannerState.failed
              : SyncBannerState.online;

  /// Human summary of what is waiting: "2 bills + 3 changes".
  String get _queuedLabel {
    final parts = <String>[
      if (_pendingCount > 0) '$_pendingCount bill(s)',
      if (_pendingMutationCount > 0) '$_pendingMutationCount change(s)',
    ];
    return parts.join(' + ');
  }

  String get bannerText {
    final queued = _queuedLabel;
    return switch (banner) {
      SyncBannerState.online => pendingTotal > 0
          ? 'Back online — syncing $queued…'
          : 'Online — all records synced',
      SyncBannerState.offline => pendingTotal > 0
          ? 'Offline — $queued queued, work continues'
          : 'Offline — everything works, will sync later',
      SyncBannerState.syncing => pendingTotal > 0
          ? 'Syncing $queued…'
          : 'Syncing…',
      SyncBannerState.failed => _lastError,
    };
  }

  void start() {
    _subscription ??= _connectivity.onConnectivityChanged.listen((results) {
      final hasLink = !results.contains(ConnectivityResult.none);
      if (hasLink) {
        // Link up: verify the Laravel backend is actually reachable before
        // flipping the banner green (hotel/coffee-shop portals fake Wi-Fi).
        unawaited(probe());
      } else {
        _online = false;
        notifyListeners();
      }
      onLinkChanged?.call(hasLink);
    });
  }

  /// Fired on every link change; SyncOrchestrator hooks `kick()` here.
  void Function(bool hasLink)? onLinkChanged;

  /// Cheap reachability probe: authenticated HEAD against /api/user.
  /// 401 still means "server reachable" — only sockets/timeouts are offline.
  Future<bool> probe() async {
    try {
      await _client.raw.head('/user');
      _online = true;
      _lastError = '';
    } catch (error) {
      final message = error.toString().toLowerCase();
      final reachable = message.contains('401') ||
          message.contains('unauthenticated') ||
          message.contains('status code of 401');
      _online = reachable;
      if (!reachable) _online = false;
    }
    notifyListeners();
    return _online;
  }

  void setSyncing(bool value) {
    if (_syncing == value) return;
    _syncing = value;
    notifyListeners();
  }

  void setPendingCount(int value) {
    if (_pendingCount == value) return;
    _pendingCount = value;
    notifyListeners();
  }

  void setPendingMutationCount(int value) {
    if (_pendingMutationCount == value) return;
    _pendingMutationCount = value;
    notifyListeners();
  }

  // -- OfflineStatusSink ----------------------------------------------

  @override
  void markServedFromCache(String source) {
    if (!_staleSources.add(source)) return;
    notifyListeners();
  }

  @override
  void markFresh(String source) {
    if (!_staleSources.remove(source)) return;
    notifyListeners();
  }

  @override
  void markQueued() {
    unawaited(refreshQueued());
  }

  /// Recount both outboxes. Called on boot, after every sync pass and after
  /// any write is queued.
  Future<void> refreshQueued() async {
    try {
      final bills = await _outbox.pendingCount(storeId: _activeStoreId);
      final changes = await _mutations.pendingCount(storeId: _activeStoreId);
      _pendingCount = bills;
      _pendingMutationCount = changes;
      notifyListeners();
    } catch (_) {
      // SQLite hiccup: keep the last known counts rather than crash the UI.
    }
  }

  void setLastError(String value) {
    _lastError = value;
    notifyListeners();
  }

  void markSynced() {
    _lastSyncAt = DateTime.now();
    _lastError = '';
    notifyListeners();
  }

  @override
  void dispose() {
    _subscription?.cancel();
    super.dispose();
  }
}
