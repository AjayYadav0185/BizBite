// Screen-level offline bookkeeping shared between the repositories (writers)
// and the UI (readers).
//
// One [OfflineStatusSink] instance — `SyncController` in production — records
// which screens are currently painting SQLite data instead of fresh server
// data, so each screen can show an honest "Cached" chip while the global
// banner explains the sync state.
library;

/// Stable identifiers for every cache the app keeps. Used both as the
/// `source` on [OfflineStatusSink] and as the `CacheKeys` prefix family.
class OfflineSources {
  OfflineSources._();

  static const String menu = 'menu';
  static const String queue = 'orders-queue';
  static const String shifts = 'shifts';
  static const String reports = 'reports';
  static const String tables = 'tables';
  static const String campaigns = 'campaigns';
  static const String staff = 'staff';
  static const String wallet = 'wallet';
}

/// Implemented by `SyncController`.
///
/// Declared as a `mixin class` so the controller can `with` it (no codegen,
/// no inheritance gymnastics) while repositories only need the interface.
abstract mixin class OfflineStatusSink {
  /// `source` was served from SQLite because the network read failed.
  void markServedFromCache(String source);

  /// `source` was refreshed from the server.
  void markFresh(String source);

  /// A mutation was parked in the outbox — refresh the pending counters so
  /// the banner (and any open screen) updates immediately.
  void markQueued();
}
