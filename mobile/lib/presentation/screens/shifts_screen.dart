import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../features/ops/data/models/shift_models.dart';
import '../../features/ops/shifts_controller.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/amount.dart';

/// Cash-drawer screen: live banner for the open shift (with Open/Close
/// actions and the counted-vs-expected variance on close) plus the
/// 30-session history from `GET /api/shifts`.
class ShiftsScreen extends StatefulWidget {
  const ShiftsScreen({super.key, required this.controller});

  final ShiftsController controller;

  @override
  State<ShiftsScreen> createState() => _ShiftsScreenState();
}

class _ShiftsScreenState extends State<ShiftsScreen> {
  @override
  void initState() {
    super.initState();
    widget.controller.load();
  }

  void _snack(String message, {bool ok = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(message),
      backgroundColor: ok ? BizBiteTheme.success : AppColors.error,
    ));
  }

  Future<void> _openShift() async {
    final cashCtrl = TextEditingController(text: '0');
    final notesCtrl = TextEditingController();

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Open shift'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextFormField(
              controller: cashCtrl,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Opening cash in drawer (₹)',
                prefixText: '₹ ',
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: notesCtrl,
              maxLength: 200,
              decoration: const InputDecoration(
                labelText: 'Notes (optional)',
                hintText: 'e.g. morning counter',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Back'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Open'),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    final error = await widget.controller.open(
      openingCash: double.tryParse(cashCtrl.text.trim()) ?? 0,
      notes: notesCtrl.text.trim(),
    );
    if (!mounted) return;
    if (error != null) {
      _snack(error);
    } else {
      _snack('Shift opened.', ok: true);
    }
  }

  Future<void> _closeShift(int shiftId) async {
    final cashCtrl = TextEditingController(text: '0');

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Close shift'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
              'Count the cash in the drawer and enter the total below. '
              'The server compares it with the expected amount.',
              style: TextStyle(color: AppColors.muted, fontSize: 12.5),
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: cashCtrl,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Counted cash (₹)',
                prefixText: '₹ ',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Back'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Close shift'),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    final error = await widget.controller.close(
      shiftId: shiftId,
      closingCash: double.tryParse(cashCtrl.text.trim()) ?? 0,
    );
    if (!mounted) return;
    if (error != null) {
      _snack(error);
      return;
    }
    final variance = widget.controller.lastVariance ?? 0;
    _snack(
      variance == 0
          ? 'Shift closed — drawer balanced.'
          : 'Shift closed — variance ${variance > 0 ? '+' : ''}${inr(variance)} '
              '${variance > 0 ? '(extra cash)' : '(short)'}',
      ok: true,
    );
  }

  @override
  Widget build(BuildContext context) {
    final controller = widget.controller;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Shifts'),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
      ),
      floatingActionButton: ListenableBuilder(
        listenable: controller,
        builder: (context, _) {
          if (controller.currentShift != null) return const SizedBox.shrink();
          return FloatingActionButton.extended(
            onPressed: controller.mutating ? null : _openShift,
            icon: const Icon(Icons.play_circle_rounded),
            label: const Text('Open shift'),
          );
        },
      ),
      body: ListenableBuilder(
        listenable: controller,
        builder: (context, _) {
          if (controller.loading && controller.shifts.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }
          if (controller.error.isNotEmpty && controller.shifts.isEmpty) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(controller.error,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: AppColors.muted)),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: controller.load,
                    child: const Text('Retry'),
                  ),
                ],
              ),
            );
          }
          return RefreshIndicator(
            onRefresh: controller.load,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
              children: [
                _currentBanner(controller),
                const SizedBox(height: 18),
                const Text(
                  'HISTORY',
                  style: TextStyle(
                    fontSize: 10.5,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 1.2,
                    color: AppColors.muted,
                  ),
                ),
                const SizedBox(height: 8),
                if (controller.shifts.isEmpty)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 32),
                    child: Center(
                      child: Text(
                        'No shifts recorded yet.\nOpen one before your first bill of the day.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: AppColors.muted),
                      ),
                    ),
                  )
                else
                  ...controller.shifts.map(_shiftTile),
              ],
            ),
          );
        },
      ),
    );
  }


  Widget _currentBanner(ShiftsController controller) {
    final shift = controller.currentShift;
    if (shift == null) {
      return Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: BizBiteTheme.hairline),
        ),
        child: const Row(
          children: [
            Icon(Icons.power_off_rounded, color: AppColors.muted),
            SizedBox(width: 12),
            Expanded(
              child: Text(
                'No shift is open. Tap “Open shift” to start the cash drawer.',
                style: TextStyle(color: AppColors.muted, fontSize: 13),
              ),
            ),
          ],
        ),
      );
    }

    final openedAt = shift.openedAt == null
        ? ''
        : DateFormat('d MMM, HH:mm').format(shift.openedAt!.toLocal());

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppColors.infoBg, Colors.white],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: BizBiteTheme.brand),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.point_of_sale_rounded,
                  color: AppColors.primaryDeep),
              const SizedBox(width: 10),
              const Expanded(
                child: Text(
                  'Shift in progress',
                  style: TextStyle(
                    fontWeight: FontWeight.w800,
                    color: AppColors.primaryDeep,
                  ),
                ),
              ),
              if (controller.mutating)
                const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            'Opened $openedAt by ${shift.userName.isNotEmpty ? shift.userName : 'you'}',
            style: const TextStyle(fontSize: 12.5, color: AppColors.slate600),
          ),
          const SizedBox(height: 4),
          Text(
            'Opening cash: ${inr(shift.openingCash)}',
            style: const TextStyle(
              fontSize: 13.5,
              fontWeight: FontWeight.w700,
              color: AppColors.ink,
            ),
          ),
          const SizedBox(height: 12),
          FilledButton.icon(
            onPressed: controller.mutating ? null : () => _closeShift(shift.id),
            style: FilledButton.styleFrom(
              backgroundColor: AppColors.errorDeep,
            ),
            icon: const Icon(Icons.stop_circle_rounded, size: 18),
            label: const Text('Close shift & count drawer'),
          ),
        ],
      ),
    );
  }


  Widget _shiftTile(Shift shift) {
    final df = DateFormat('d MMM, HH:mm');
    final openedAt =
        shift.openedAt == null ? '—' : df.format(shift.openedAt!.toLocal());
    final closedAt = shift.closedAt == null
        ? 'in progress'
        : df.format(shift.closedAt!.toLocal());

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: BizBiteTheme.hairline),
      ),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: shift.isOpen
                  ? BizBiteTheme.successContainer
                  : AppColors.surfaceMuted,
              borderRadius: BorderRadius.circular(11),
            ),
            child: Icon(
              shift.isOpen ? Icons.lock_open_rounded : Icons.lock_rounded,
              size: 18,
              color:
                  shift.isOpen ? AppColors.primaryDeep : AppColors.slate500,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '$openedAt → $closedAt',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: AppColors.ink,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  'Opened ${inr(shift.openingCash)} · '
                  'closed ${inr(shift.closingCash)}'
                  '${!shift.isOpen ? ' · expected ${inr(shift.expectedCash)}' : ''}',
                  style: const TextStyle(
                    fontSize: 11.5,
                    color: AppColors.muted,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

