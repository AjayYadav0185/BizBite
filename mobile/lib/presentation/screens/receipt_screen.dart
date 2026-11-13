import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/utils/india.dart';
import '../../features/auth/session_controller.dart';
import '../../features/orders/data/models/order_models.dart';
import '../../features/orders/data/models/order_receipt_model.dart';
import '../../features/orders/order_flow_controller.dart';
import '../../features/orders/receipt_document.dart';
import '../services/print_settings.dart';
import '../services/receipt_printer.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/thermal_paper.dart';

/// Screen 3 — settled bill: thermal preview + Print bill / KOT / WhatsApp.
///
/// The preview renders [ReceiptDocument] — the exact fixed-width lines the
/// Bluetooth printer receives — so the cashier verifies what reaches paper.
/// Also used for reprints from the order history ([onClose] pops back).
class ReceiptScreen extends StatefulWidget {
  const ReceiptScreen({
    super.key,
    required this.receipt,
    this.session,
    required this.printer,
    this.orderFlow,
    this.printSettings,
    this.onClose,
  });

  final OrderReceiptModel receipt;
  final SessionController? session;
  final OrderFlowController? orderFlow;
  final ReceiptPrinter printer;
  final PrintSettings? printSettings;

  /// Overrides the "New order" action (reprint flow closes the page).
  final VoidCallback? onClose;

  @override
  State<ReceiptScreen> createState() => _ReceiptScreenState();
}

class _ReceiptScreenState extends State<ReceiptScreen> {
  bool _printing = false;
  String _printStatus = '';
  bool _printOk = true;
  bool _showKot = false;

  int get _columns => widget.printSettings?.columns ?? 32;

  OrderReceiptModel get _r => widget.receipt;

  Future<void> _print({required bool kot}) async {
    if (_printing) return;
    setState(() {
      _printing = true;
      _printStatus = '';
    });

    final ok = kot
        ? await widget.printer.printKot(_r, columns: _columns)
        : await widget.printer.printReceipt(_r, columns: _columns);
    if (!mounted) return;
    setState(() {
      _printing = false;
      _printOk = ok;
      _printStatus = ok
          ? (kot ? 'KOT sent to the printer.' : 'Bill sent to the printer.')
          : (widget.printer.lastError.isNotEmpty
              ? widget.printer.lastError
              : 'Printing failed.');
    });
  }

  Future<void> _whatsapp() async {
    final uri = India.whatsappUri(ReceiptDocument.whatsappText(_r),
        mobile: _r.customerPhone);
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('WhatsApp is not installed on this device.')),
      );
    }
  }

  void _close() {
    if (widget.onClose != null) {
      widget.onClose!();
    } else {
      widget.orderFlow?.clearReceipt();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.printerBed,
      body: SafeArea(
        child: Column(
          children: [
            _statusHeader(),
            _copyToggle(),
            Expanded(
              child: Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  child: _paper(),
                ),
              ),
            ),
            _footer(),
          ],
        ),
      ),
    );
  }

  Widget _statusHeader() {
    final offline = _r.isOffline;
    final reprint = _r.isReprint;
    final fg = offline ? AppColors.warningDeep : AppColors.successDeep;
    final title = offline
        ? 'Saved offline — will sync'
        : reprint
            ? 'Bill ${_r.orderNumber}'
            : '₹${India.number(_r.totalAmount)} received';

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: offline ? AppColors.warningBg : AppColors.successBg,
        borderRadius: BorderRadius.circular(AppRadius.lg),
      ),
      child: Row(
        children: [
          Icon(
            offline
                ? Icons.wifi_off_rounded
                : reprint
                    ? Icons.history_rounded
                    : Icons.check_circle_rounded,
            size: 30,
            color: fg,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    style: TextStyle(
                        fontSize: 17, fontWeight: FontWeight.w800, color: fg)),
                const SizedBox(height: 2),
                Text(
                  [
                    _r.paymentMode.label,
                    if (!reprint) 'Bill #${_r.orderNumber}',
                    if (_r.changeAmount > 0)
                      'Return change ₹${India.number(_r.changeAmount)}',
                  ].join(' · '),
                  style: TextStyle(
                      fontSize: 12.5, fontWeight: FontWeight.w600, color: fg),
                ),
                if (offline)
                  Text(
                    'Queued locally — auto-syncs when online. Same bill, no duplicates.',
                    style: TextStyle(fontSize: 11.5, color: fg),
                  ),
                if (_printStatus.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    _printStatus,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: _printOk ? fg : AppColors.errorDeep,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// Switch the preview between the customer bill and the kitchen ticket.
  Widget _copyToggle() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
      child: SegmentedButton<bool>(
        style: SegmentedButton.styleFrom(
          backgroundColor: Colors.white10,
          foregroundColor: Colors.white70,
          selectedBackgroundColor: Colors.white,
          selectedForegroundColor: AppColors.ink,
          side: const BorderSide(color: Colors.white24),
        ),
        showSelectedIcon: false,
        segments: const [
          ButtonSegment(value: false, label: Text('Bill'), icon: Icon(Icons.receipt_long_rounded, size: 18)),
          ButtonSegment(value: true, label: Text('KOT'), icon: Icon(Icons.soup_kitchen_rounded, size: 18)),
        ],
        selected: {_showKot},
        onSelectionChanged: (v) => setState(() => _showKot = v.first),
      ),
    );
  }

  Widget _paper() {
    final doc = ReceiptDocument(columns: _columns);
    final lines = _showKot ? doc.kot(_r) : doc.bill(_r);
    // Size the paper so `columns` monospace characters fit exactly.
    final width = _columns <= 32 ? 300.0 : 360.0;
    final fontSize = (width - 24) / _columns / 0.6;

    return SizedBox(
      width: width,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const TearEdge(color: Colors.white, isTop: true),
          Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
            child: ReceiptLinesView(lines: lines, fontSize: fontSize),
          ),
          const TearEdge(color: Colors.white, isTop: false),
        ],
      ),
    );
  }

  Widget _footer() {
    Widget action(IconData icon, String label, VoidCallback? onTap, {Color? color}) {
      return Expanded(
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(14),
          child: Container(
            height: 62,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: Colors.white24),
            ),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(icon, color: color ?? Colors.white, size: 22),
                const SizedBox(height: 4),
                Text(label,
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.w700)),
              ],
            ),
          ),
        ),
      );
    }

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              action(
                Icons.print_rounded,
                _printing ? 'Printing…' : 'Print bill',
                _printing ? null : () => _print(kot: false),
              ),
              const SizedBox(width: 10),
              action(
                Icons.soup_kitchen_rounded,
                'Print KOT',
                _printing ? null : () => _print(kot: true),
              ),
              const SizedBox(width: 10),
              action(Icons.chat_rounded, 'WhatsApp', _whatsapp,
                  color: AppColors.whatsapp),
            ],
          ),
          const SizedBox(height: 10),
          SizedBox(
            width: double.infinity,
            height: 54,
            child: FilledButton.icon(
              onPressed: _close,
              style: FilledButton.styleFrom(
                backgroundColor: BizBiteTheme.brand,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16)),
              ),
              icon: Icon(
                  widget.onClose != null ? Icons.arrow_back_rounded : Icons.add_rounded,
                  size: 20),
              label: Text(
                widget.onClose != null ? 'Back' : 'New order',
                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Renders [ReceiptLine]s in monospace exactly as the printer lays them out.
class ReceiptLinesView extends StatelessWidget {
  const ReceiptLinesView({super.key, required this.lines, this.fontSize = 11});

  final List<ReceiptLine> lines;
  final double fontSize;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        for (final line in lines)
          Text(
            line.text,
            maxLines: 1,
            softWrap: false,
            overflow: TextOverflow.clip,
            textAlign: switch (line.align) {
              LineAlign.center => TextAlign.center,
              LineAlign.right => TextAlign.right,
              LineAlign.left => TextAlign.left,
            },
            style: BizBiteTheme.receiptMono(
              size: line.large ? fontSize * 1.6 : fontSize,
              weight: line.bold || line.large ? FontWeight.w800 : FontWeight.w500,
              color: AppColors.paperInk,
              height: 1.3,
            ),
          ),
      ],
    );
  }
}
