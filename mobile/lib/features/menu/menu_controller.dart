import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import 'data/models/menu_response_model.dart';
import 'data/repositories/menu_repository.dart';

/// Loads and caches the store menu (`GET /api/menu`) for the POS grid.
///
/// The menu is tenant-scoped and availability-filtered server-side; this
/// controller simply guards against duplicate fetches and surfaces failures
/// as a cashier-readable message.
class MenuController with ChangeNotifier implements Listenable {
  MenuController({required this._repository});

  final MenuRepository _repository;

  MenuResponseModel? menu;
  bool loading = false;
  String? error;

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
}