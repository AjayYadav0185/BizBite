// Offline-first connectivity + sync state for the status banner.
//
// Listens to `connectivity_plus` (Wi-Fi/mobile/none) AND probes the real
// backend (`HEAD /api/user`) because captive portals and dead routers lie
// about connectivity. `pendingCount` drives the "N bills queued" banner.
import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';

import '../network/dio_client.dart';

enum SyncBannerState { online, offline, syncing, failed }

class SyncController with ChangeNotifier {
  // ignore: prefer_initializing_formals
  SyncController({required DioClient client, Connectivity? connectivity})
      : _client = client,
        _connectivity = connectivity ?? Connectivity();

  final DioClient _client;
  final Connectivity _connectivity;

  StreamSubscription<List<ConnectivityResult>>? _subscription;

  bool _online = true;
  bool _syncing = false;
  int _pendingCount = 0;
  String _lastError = '';
  DateTime? _lastSyncAt;

  bool get isOnline => _online;
  bool get isSyncing => _syncing;
  int get pendingCount => _pendingCount;
  String get lastError => _lastError;
  DateTime? get lastSyncAt => _lastSyncAt;

  SyncBannerState get banner => !_online
      ? SyncBannerState.offline
      : _syncing
          ? SyncBannerState.syncing
          : _lastError.isNotEmpty
              ? SyncBannerState.failed
              : SyncBannerState.online;

  String get bannerText => switch (banner) {
        SyncBannerState.online =>
          _pendingCount > 0 ? 'Back online — syncing $_pendingCount bill(s)…' : 'Online — all bills synced',
        SyncBannerState.offline =>
          _pendingCount > 0 ? 'Offline — $_pendingCount bill(s) queued, billing continues' : 'Offline — billing continues, will sync later',
        SyncBannerState.syncing => 'Syncing $_pendingCount bill(s)…',
        SyncBannerState.failed => _lastError,
      };

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
