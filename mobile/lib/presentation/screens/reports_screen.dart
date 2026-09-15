import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../core/sync/offline_sources.dart';
import '../../core/sync/sync_controller.dart';
import '../../features/ops/reports_controller.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/amount.dart';
import '../widgets/offline_screen_header.dart';

/// Owner "night numbers": date-range KPIs, the hourly sales curve and the
/// best-sellers board. Defaults to today; chips + pickers cover ranges.
///
/// Offline: the last cached numbers for the selected date/range are shown with
/// a "cached" strip — reports are read-only, so nothing is ever queued.
class ReportsScreen extends StatefulWidget {
  const ReportsScreen({
    super.key,
    required this.controller,
    required this.sync,
  });

  final ReportsController controller;

  /// Live connectivity + queued-work state for the offline strip.
  final SyncController sync;

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  DateTime _from = DateTime.now();
  DateTime _to = DateTime.now();

  static final DateFormat _api = DateFormat('yyyy-MM-dd');
  static final DateFormat _label = DateFormat('d MMM');

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      widget.controller.load(from: _api.format(_from), to: _api.format(_to));
    });
  }

  Future<void> _pickFrom() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _from,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now(),
    );
    if (picked == null) return;
    setState(() {
      _from = picked;
      if (_to.isBefore(_from)) _to = _from;
    });
    widget.controller.load(from: _api.format(_from), to: _api.format(_to));
  }

  Future<void> _pickTo() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _to,
      firstDate: _from,
      lastDate: DateTime.now(),
    );
    if (picked == null) return;
    setState(() => _to = picked);
    widget.controller.load(from: _api.format(_from), to: _api.format(_to));
  }

  void _range({required int backDays}) {
    setState(() {
      _to = DateTime.now();
      _from = _to.subtract(Duration(days: backDays));
    });
    widget.controller.load(from: _api.format(_from), to: _api.format(_to));
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Reports'),
          backgroundColor: Colors.white,
          surfaceTintColor: Colors.white,
          bottom: const TabBar(
            tabs: [
              Tab(text: 'Overview'),
              Tab(text: 'Hourly'),
              Tab(text: 'Best sellers'),
            ],
          ),
        ),
        body: ListenableBuilder(
          listenable: widget.controller,
          builder: (context, _) {
            final controller = widget.controller;
            return Column(
              children: [
                OfflineScreenHeader(
                  sync: widget.sync,
                  source: OfflineSources.reports,
                  onRetry: () async => widget.controller.load(
                    from: _api.format(_from),
                    to: _api.format(_to),
                  ),
                ),
                _rangeBar(controller),
                Expanded(
                  child: controller.loading && controller.rangeReport == null
                      ? const Center(child: CircularProgressIndicator())
                      : controller.error.isNotEmpty &&
                              controller.rangeReport == null
                          ? Center(
                              child: Column(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(controller.error,
                                      style: const TextStyle(
                                          color: AppColors.muted)),
                                  const SizedBox(height: 12),
                                  FilledButton(
                                    onPressed: () => controller.load(
                                        from: _api.format(_from),
                                        to: _api.format(_to)),
                                    child: const Text('Retry'),
                                  ),
                                ],
                              ),
                            )
                          : TabBarView(
                              children: [
                                _overview(controller),
                                _hourly(controller),
                                _bestSellers(controller),
                              ],
                            ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _rangeBar(ReportsController controller) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: BizBiteTheme.hairline)),
      ),
      child: Wrap(
        spacing: 8,
        runSpacing: 8,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          ActionChip(
            avatar: const Icon(Icons.calendar_today_rounded, size: 15),
            label: Text('${_label.format(_from)} → ${_label.format(_to)}'),
            onPressed: _pickFrom,
          ),
          TextButton(onPressed: _pickTo, child: const Text('Change end')),
          ActionChip(
            label: const Text('Today'),
            onPressed: () => _range(backDays: 0),
          ),
          ActionChip(
            label: const Text('7 days'),
            onPressed: () => _range(backDays: 6),
          ),
          ActionChip(
            label: const Text('30 days'),
            onPressed: () => _range(backDays: 29),
          ),
          if (controller.loading)
            const SizedBox(
              width: 14,
              height: 14,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
        ],
      ),
    );
  }

  Widget _overview(ReportsController controller) {
    final report = controller.rangeReport;
    if (report == null) return const SizedBox.shrink();

    final kpis = <(String, double, IconData, Color)>[
      ('Revenue', report.revenue, Icons.currency_rupee_rounded,
          AppColors.primaryDeep),
      ('Net of refunds', report.net, Icons.account_balance_wallet_rounded,
          AppColors.primaryDeep),
      ('Average bill', report.averageBill, Icons.receipt_long_rounded,
          AppColors.infoCyan),
      ('Discounts', report.discounts, Icons.sell_rounded,
          AppColors.warningDeep),
      ('Refunds', report.refunds, Icons.replay_rounded, AppColors.errorDeep),
      ('Bills', report.bills.toDouble(), Icons.confirmation_number_rounded,
          AppColors.slate600),
    ];

    return RefreshIndicator(
      onRefresh: () =>
          controller.load(from: _api.format(_from), to: _api.format(_to)),
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: 1.55,
            children: kpis
                .map((kpi) => _kpiCard(kpi.$1, kpi.$2, kpi.$3, kpi.$4))
                .toList(),
          ),
          const SizedBox(height: 12),
          Text(
            'Settled bills only — voided bills are excluded by the server.',
            style: TextStyle(
              fontSize: 11.5,
              color: AppColors.muted.withValues(alpha: 0.9),
            ),
          ),
        ],
      ),
    );
  }

  Widget _kpiCard(String label, double value, IconData icon, Color color) {
    final isCount = label == 'Bills';
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: BizBiteTheme.hairline),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 18, color: color),
          const Spacer(),
          FittedBox(
            child: Text(
              isCount ? value.toStringAsFixed(0) : inr(value),
              style: TextStyle(
                fontSize: 19,
                fontWeight: FontWeight.w800,
                color: color,
              ),
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w600,
              color: AppColors.muted,
            ),
          ),
        ],
      ),
    );
  }


  Widget _hourly(ReportsController controller) {
    final rows = controller.hourly;
    final busy = rows.where((r) => r.bills > 0).toList(growable: false);
    final maxRevenue =
        busy.fold<double>(0, (m, r) => r.revenue > m ? r.revenue : m);

    return RefreshIndicator(
      onRefresh: () =>
          controller.load(from: _api.format(_from), to: _api.format(_to)),
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          if (busy.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 48),
              child: Center(
                child: Text(
                  'No settled bills in this range yet.',
                  style: TextStyle(color: AppColors.muted),
                ),
              ),
            )
          else
            ...busy.map(
              (row) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Row(
                  children: [
                    SizedBox(
                      width: 46,
                      child: Text(
                        row.hour,
                        style: const TextStyle(
                          fontSize: 11.5,
                          fontWeight: FontWeight.w700,
                          color: AppColors.muted,
                        ),
                      ),
                    ),
                    Expanded(
                      child: Stack(
                        alignment: Alignment.centerLeft,
                        children: [
                          FractionallySizedBox(
                            widthFactor: maxRevenue <= 0
                                ? 0
                                : row.revenue / maxRevenue,
                            child: Container(
                              height: 22,
                              decoration: BoxDecoration(
                                gradient: const LinearGradient(
                                  colors: [
                                    AppColors.primary,
                                    AppColors.primaryDeep,
                                  ],
                                ),
                                borderRadius: BorderRadius.circular(7),
                              ),
                            ),
                          ),
                          Padding(
                            padding: const EdgeInsets.only(left: 8),
                            child: Text(
                              '${inr(row.revenue)} · ${row.bills} bill(s)',
                              style: const TextStyle(
                                fontSize: 10.5,
                                fontWeight: FontWeight.w800,
                                color: AppColors.ink,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }


  Widget _bestSellers(ReportsController controller) {
    final rows = controller.bestSellers;
    return RefreshIndicator(
      onRefresh: () =>
          controller.load(from: _api.format(_from), to: _api.format(_to)),
      child: rows.isEmpty
          ? ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              children: const [
                Padding(
                  padding: EdgeInsets.symmetric(vertical: 48),
                  child: Center(
                    child: Text(
                      'No item sales in this range yet.',
                      style: TextStyle(color: AppColors.muted),
                    ),
                  ),
                ),
              ],
            )
          : ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: rows.length,
              separatorBuilder: (_, _) => const SizedBox(height: 8),
              itemBuilder: (context, index) {
                final row = rows[index];
                return Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(13),
                    border: Border.all(color: BizBiteTheme.hairline),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 28,
                        height: 28,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: index < 3
                              ? BizBiteTheme.brand
                              : AppColors.surfaceMuted,
                          borderRadius: BorderRadius.circular(9),
                        ),
                        child: Text(
                          '${index + 1}',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                            color: index < 3
                                ? Colors.white
                                : AppColors.slate500,
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          row.name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 13.5,
                            fontWeight: FontWeight.w700,
                            color: AppColors.ink,
                          ),
                        ),
                      ),
                      Text(
                        '${row.quantity} sold · ${inr(row.revenue)}',
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: AppColors.muted,
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
    );
  }
}

