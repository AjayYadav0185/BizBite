import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import 'data/models/shift_models.dart';
import 'data/repositories/ops_repository.dart';

/// State holder for cash-drawer shifts (open / close / history).
///
/// `GET /api/shifts` returns the latest 30 sessions; [currentShift] exposes
/// the open one (if any) so the drawer screen can show a live banner.
class ShiftsController extends ChangeNotifier {
  ShiftsController({required this._repository});

  final OpsRepository _repository;

  List<Shift> shifts = const [];
  bool loading = false;
  bool mutating = false;
  String error = '';

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
    notifyListeners();
    try {
      await action();
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
