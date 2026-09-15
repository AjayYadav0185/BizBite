import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import '../../core/sync/offline_queued_exception.dart';
import 'data/models/shift_models.dart';
import 'data/repositories/ops_repository.dart';

/// State holder for cash-drawer shifts (open / close / history).
///
/// `GET /api/shifts` returns the latest 30 sessions; [currentShift] exposes
/// the open one (if any) so the drawer screen can show a live banner.
/// Offline the list comes from the SQLite cache and open/close are queued in
/// the mutation outbox (the drawer is added/closed locally right away).
class ShiftsController extends ChangeNotifier {
  ShiftsController({required this._repository});

  final OpsRepository _repository;

  List<Shift> shifts = const [];
  bool loading = false;
  bool mutating = false;
  String error = '';

  /// Set when the last open/close was queued for sync instead of reaching the
  /// server, so the screen can confirm it to the cashier.
  String? queuedNotice;

  /// Variance of the most recent close (server: closing − expected).
  double? lastVariance;

  /// The still-open shift, if any (newest-first list from the API).
  Shift? get currentShift {
    for (final shift in shifts) {
      if (shift.isOpen) return shift;
    }
    return null;
  }

  Future<void> load() async {
    if (loading) return;
    loading = true;
    error = '';
    notifyListeners();

    try {
      shifts = await _repository.fetchShifts();
      error = '';
    } on ApiException catch (e) {
      error = e.message;
      if (e.isAuthError) rethrow;
    } catch (_) {
      error = 'Could not load shifts.';
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  /// Open a drawer session. Returns `null` on success or the error message.
  Future<String?> open({required double openingCash, String? notes}) async {
    return _mutate(() async {
      await _repository.openShift(openingCash: openingCash, notes: notes);
      await load();
    });
  }

  /// Close the drawer and remember the server-computed variance.
  /// Returns `null` on success or the error message.
  Future<String?> close({
    required int shiftId,
    required double closingCash,
  }) async {
    return _mutate(() async {
      final result = await _repository.closeShift(
        shiftId: shiftId,
        closingCash: closingCash,
      );
      lastVariance = result.variance;
      await load();
    });
  }

  Future<String?> _mutate(Future<void> Function() action) async {
    if (mutating) return null;
    mutating = true;
    queuedNotice = null;
    notifyListeners();
    try {
      await action();
      return null;
    } on OfflineQueuedException catch (queued) {
      // Saved on the device and applied to the cached drawer list; the
      // orchestrator replays it as soon as the server is reachable again.
      queuedNotice = queued.message;
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (_) {
      return 'Something went wrong. Please try again.';
    } finally {
      mutating = false;
      notifyListeners();
    }
  }
}
