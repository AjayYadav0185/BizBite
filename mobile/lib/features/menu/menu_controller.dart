import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import 'data/models/menu_response_model.dart';
import 'data/repositories/menu_repository.dart';

/// Loads and caches the store menu (`GET /api/menu`) for the POS grid.
///
/// The menu is tenant-scoped and availability-filtered server-side; this
/// controller simply guards against duplicate fetches and surfaces failures
/// as a cashier-readable message.
///
/// Admin mutations (add/edit/delete) call the admin-only endpoints and then
/// `refresh()` so the POS grid and Store console stay in sync.
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

  bool _hasLoaded = false;

  bool get isLoaded => menu != null;

  /// Fetch the menu exactly once. Subsequent calls are no-ops until the
  /// session is refreshed (a future "refresh" action can set `_hasLoaded =
  /// false` again).
  Future<void> load() async {
    if (loading || _hasLoaded) return;
    loading = true;
    error = null;
    notifyListeners();

    try {
      menu = await _repository.fetchMenu();
      _hasLoaded = true;
    } on ApiException catch (exception) {
      error = exception.message;
    }

    loading = false;
    notifyListeners();
  }

  /// Force a re-fetch (pull-to-refresh + after every admin mutation).
  Future<void> refresh() async {
    _hasLoaded = false;
    loading = true;
    error = null;
    notifyListeners();
    try {
      menu = await _repository.fetchMenu();
      _hasLoaded = true;
    } on ApiException catch (exception) {
      error = exception.message;
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