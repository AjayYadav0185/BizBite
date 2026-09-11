import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import 'data/models/menu_response_model.dart';
import 'data/repositories/menu_repository.dart';

/// Offline-first menu loader for the POS grid.
///
/// Contract: `load()` paints the last-good SQLite snapshot instantly (works
/// with zero bars), then refreshes from `GET /api/menu` in the background
/// and overwrites the cache wholesale (catalog is server-wins LWW).
/// `source` tells the banner whether the grid is fresh, cached or stale.
enum MenuSource { cache, syncing, synced, stale }

class MenuController with ChangeNotifier implements Listenable {
  MenuController({required this._repository});

  final MenuRepository _repository;

  MenuResponseModel? menu;
  bool loading = false;
  String? error;

  /// Last admin mutation failure (surfaced as a snackbar by AdminScreen).
  /// Null on success.
  String? lastMutationError;

  /// True while an admin add/edit/delete is in flight (buttons show spinners).
  bool mutating = false;

  MenuSource source = MenuSource.cache;

  bool _hasLoaded = false;

  bool get isLoaded => menu != null;
  bool get isStale => source == MenuSource.stale || source == MenuSource.cache;
  bool get isCacheOnly =>
      menu != null &&
      (source == MenuSource.cache || source == MenuSource.stale);

  /// Offline-first fetch: cache paints instantly, network refreshes behind.
  Future<void> load() async {
    if (loading || _hasLoaded) return;
    loading = true;
    error = null;
    notifyListeners();

    // 1. Instant paint from SQLite (or empty on first-ever launch).
    try {
      final cached = await _repository.loadCached();
      if (cached.items.isNotEmpty || cached.categories.isNotEmpty) {
        menu = cached;
        source = MenuSource.cache;
        notifyListeners();
      }
    } catch (_) {
      // Corrupt/empty cache: fall through to network attempt.
    }

    // 2. Background refresh (server-wins). Offline keeps the cache.
    source = MenuSource.syncing;
    notifyListeners();
    try {
      menu = await _repository.fetchMenu();
      source = MenuSource.synced;
      _hasLoaded = true;
      error = null;
    } on ApiException catch (exception) {
      if (menu != null) {
        source = MenuSource.stale;
        error = null; // cached grid is usable; banner shows staleness
      } else {
        error = exception.message;
        source = MenuSource.stale;
      }
    }

    loading = false;
    notifyListeners();
  }

  /// Force a re-fetch (pull-to-refresh + after every admin mutation).
  Future<void> refresh() async {
    _hasLoaded = false;
    loading = true;
    error = null;
    source = MenuSource.syncing;
    notifyListeners();
    try {
      menu = await _repository.fetchMenu();
      source = MenuSource.synced;
      _hasLoaded = true;
    } on ApiException catch (exception) {
      if (menu != null) {
        source = MenuSource.stale;
      } else {
        error = exception.message;
        source = MenuSource.stale;
      }
    }
    loading = false;
    notifyListeners();
  }

  /// Runs [action], then refreshes. Returns true on success; on failure
  /// sets [lastMutationError] so the UI can show a snackbar.
  Future<bool> _mutate(Future<void> Function() action) async {
    mutating = true;
    lastMutationError = null;
    notifyListeners();
    try {
      await action();
      await refresh();
      return true;
    } on ApiException catch (exception) {
      lastMutationError = exception.message;
      return false;
    } finally {
      mutating = false;
      notifyListeners();
    }
  }

  Future<bool> addCategory(String name) =>
      _mutate(() async {
        await _repository.createCategory(name: name);
      });

  Future<bool> renameCategory(int id, String name) =>
      _mutate(() async {
        await _repository.renameCategory(id: id, name: name);
      });

  Future<bool> addItem({
    required int categoryId,
    required String name,
    required double price,
  }) =>
      _mutate(() async {
        await _repository.createItem(
          categoryId: categoryId,
          name: name,
          price: price,
        );
      });

  Future<bool> editItem({
    required int id,
    required int categoryId,
    required String name,
    required double price,
  }) =>
      _mutate(() async {
        await _repository.updateItem(
          id: id,
          categoryId: categoryId,
          name: name,
          price: price,
        );
      });

  Future<bool> removeItem(int id) =>
      _mutate(() => _repository.deleteItem(id));
}
