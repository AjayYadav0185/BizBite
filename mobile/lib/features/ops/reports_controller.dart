import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import 'data/models/report_models.dart';
import 'data/repositories/ops_repository.dart';

/// State holder for the owner "night numbers" screen:
///
///   - [hourly]    → GET /api/reports/hourly for the selected day
///   - [bestSellers] → GET /api/reports/best-sellers for the range
///   - [rangeReport] → GET /api/reports/range KPI rollup
///
/// All three load together so the tabs never mix stale/fresh data.
class ReportsController extends ChangeNotifier {
  ReportsController({required this._repository});

  final OpsRepository _repository;

  List<HourlyRow> hourly = const [];
  List<BestSeller> bestSellers = const [];
  RangeReport? rangeReport;

  String from = '';
  String to = '';
  bool loading = false;
  String error = '';

  /// Load all three reports for `[from, to]` (`Y-m-d` strings).
  Future<void> load({required String from, required String to}) async {
    if (loading) return;
    loading = true;
    this.from = from;
    this.to = to;
    error = '';
    notifyListeners();

    try {
      final results = await Future.wait([
        _repository.hourly(date: from),
        _repository.bestSellers(from: from, to: to),
        _repository.range(from: from, to: to),
      ]);
      hourly = (results[0] as ReportHourlyBundle).rows;
      bestSellers = results[1] as List<BestSeller>;
      rangeReport = results[2] as RangeReport;
      error = '';
    } on ApiException catch (e) {
      error = e.message;
      if (e.isAuthError) rethrow;
    } catch (_) {
      error = 'Could not load reports.';
    } finally {
      loading = false;
      notifyListeners();
    }
  }
}
