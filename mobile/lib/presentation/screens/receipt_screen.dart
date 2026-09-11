import 'package:flutter/material.dart';

import '../../features/auth/session_controller.dart';
import '../../features/orders/data/models/order_models.dart';
import '../../features/orders/data/models/order_receipt_model.dart';
import '../../features/orders/order_flow_controller.dart';
import '../services/receipt_printer.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/thermal_paper.dart';

/// Screen 3 — Receipt Preview (thermal-printer optimized).
///
/// Digital mirror of the Laravel `billing-dashboard` `#thermal-receipt`
/// block (`resources/views/livewire/pos/billing-dashboard.blade.php`):
/// the same 72mm / 32-col mono layout, same line order, same store
/// template lines (`print_header` / `print_footer` saved via the web
/// Receipt Customizer and served inside `POST /api/orders` → `store`).
/// The footer holds the two primary actions: "Print Bill via Bluetooth"
/// (bottom) and "New Order".
///
/// Screen preview, Bluetooth ESC/POS bytes ([ReceiptPrinter]) and the web
/// `window.print()` block all render this one template, so what the
/// cashier verifies on-screen is what reaches paper.
class ReceiptScreen extends StatefulWidget {
  const ReceiptScreen({
    super.key,
    required this.receipt,
    required this.session,
    required this.orderFlow,
    required this.printer,
  });

  final OrderReceiptModel receipt;
  final SessionController session;
  final OrderFlowController orderFlow;
  final ReceiptPrinter printer;

  @override
  State<ReceiptScreen> createState() => _ReceiptScreenState();
}

class _ReceiptScreenState extends State<ReceiptScreen> {
  bool _printing = false;
  String _printStatus = '';

  /// On-paper ink colors — single source AppColors (spec §3).
  static const Color _ink = AppColors.paperInk;
  static const Color _inkMuted = AppColors.paperMuted;

  Future<void> _reprint() async {
    if (_printing) return;
    setState(() {
      _printing = true;
      _printStatus = '';
    });

    final ok = await widget.printer.printReceipt(widget.receipt);
    setState(() {
      _printing = false;
      _printStatus = ok
          ? 'Receipt sent to the printer.'
          : (widget.printer.lastError.isNotEmpty
                ? widget.printer.lastError
                : 'Printing failed.');
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      // Dark "printer bed" — makes the paper roll the hero.
      backgroundColor: AppColors.printerBed,
      body: SafeArea(
        child: Column(
          children: [
            _statusHeader(),
            Expanded(
              child: Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(vertical: 14),
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

  /// Success Green banner — the paid/settled status signal.
  Widget _statusHeader() {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 0),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: BizBiteTheme.successContainer,
        borderRadius: BorderRadius.circular(AppRadius.lg),
      ),
      child: Row(
        children: [
          const Icon(
            Icons.check_circle_rounded,
            size: 28,
            color: BizBiteTheme.success,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Payment received',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: AppColors.successDeep,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  '${widget.receipt.paymentMode.label} · Bill #${widget.receipt.orderNumber}',
                  style: const TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w600,
                    color: AppColors.successDeep,
                  ),
                ),
                if (_printStatus.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    _printStatus,
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                      color: AppColors.successDeep,
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

  /// The thermal paper roll — jagged tear edges hugging a white sheet.
  Widget _paper() {
    return SizedBox(
      width: 320,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const TearEdge(color: Colors.white, isTop: true),
          Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 18),
            child: _receiptContent(),
          ),
          const TearEdge(color: Colors.white, isTop: false),
        ],
      ),
    );
  }

  /// Monospaced receipt body — 1:1 mirror of the Laravel `#thermal-paper`
  /// block so preview, Bluetooth ESC/POS bytes ([ReceiptPrinter]) and the
  /// web `window.print()` output are identical.
  ///
  /// Line order (lock-step with `billing-dashboard.blade.php`):
  /// header template → store name/address/phone → 32-col rule →
  /// `Bill: <n>` + date row → cashier → rule → `<qty> x <name>` rows →
  /// rule → TOTAL / Paid-via rows → rule → footer template → branding.
  Widget _receiptContent() {
    final receipt = widget.receipt;
    final store = receipt.store;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // --- Store branding: Laravel owner template lines ------------------
        if (store.printHeader.isNotEmpty)
          Text(
            store.printHeader.toUpperCase(),
            textAlign: TextAlign.center,
            style: BizBiteTheme.receiptMono(size: 12, weight: FontWeight.w800),
          ),
        Text(
          store.name.toUpperCase(),
          textAlign: TextAlign.center,
          style: BizBiteTheme.receiptMono(size: 14, weight: FontWeight.w800),
        ),
        if (store.address.isNotEmpty)
          Text(
            store.address,
            textAlign: TextAlign.center,
            style: BizBiteTheme.receiptMono(size: 12),
          ),
        if (store.phone.isNotEmpty)
          Text(
            'Ph: ${store.phone}',
            textAlign: TextAlign.center,
            style: BizBiteTheme.receiptMono(size: 12),
          ),
        _rule(),
        // --- Bill meta: `Bill: <n>` + date on one row, cashier below -------
        Row(
          children: [
            Expanded(
              child: Text(
                'Bill: ${receipt.orderNumber}',
                style: BizBiteTheme.receiptMono(
                  size: 12,
                  weight: FontWeight.w600,
                ),
              ),
            ),
            Text(
              receipt.placedAt,
              style: BizBiteTheme.receiptMono(
                size: 12,
                weight: FontWeight.w600,
              ),
            ),
          ],
        ),
        if (receipt.cashier.isNotEmpty)
          Text(
            'Cashier: ${receipt.cashier}',
            style: BizBiteTheme.receiptMono(size: 12, weight: FontWeight.w600),
          ),
        _rule(),
        // --- Items: `<qty> x <name>` … `<subtotal>` (web shape) -------------
        Text(
          'ITEM                QTY   AMOUNT',
          style: BizBiteTheme.receiptMono(
            size: 10.5,
            weight: FontWeight.w800,
            color: _inkMuted,
          ),
        ),
        const SizedBox(height: 6),
        for (final item in receipt.items)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 1),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    '${item.quantity} x ${item.foodItemName}',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: BizBiteTheme.receiptMono(
                      size: 12,
                      weight: FontWeight.w600,
                    ),
                  ),
                ),
                const SizedBox(width: 6),
                Text(
                  item.subtotal.toStringAsFixed(2),
                  style: BizBiteTheme.receiptMono(
                    size: 12,
                    weight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        _rule(),
        // --- Totals: TOTAL / Paid-via rows (web shape) -----------------------
        Row(
          children: [
            Expanded(
              child: Text(
                'TOTAL',
                style: BizBiteTheme.receiptMono(
                  size: 13,
                  weight: FontWeight.w800,
                ),
              ),
            ),
            Text(
              'Rs. ${receipt.totalAmount.toStringAsFixed(2)}',
              style: BizBiteTheme.receiptMono(
                size: 13,
                weight: FontWeight.w800,
              ),
            ),
          ],
        ),
        Row(
          children: [
            Expanded(
              child: Text(
                'Paid via',
                style: BizBiteTheme.receiptMono(
                  size: 12,
                  weight: FontWeight.w600,
                ),
              ),
            ),
            Text(
              receipt.paymentMode.label.toUpperCase(),
              style: BizBiteTheme.receiptMono(
                size: 12,
                weight: FontWeight.w600,
              ),
            ),
          ],
        ),
        _rule(),
        // --- Footer: Laravel owner template lines ---------------------------
        if (store.printFooter.isNotEmpty)
          Text(
            store.printFooter,
            textAlign: TextAlign.center,
            style: BizBiteTheme.receiptMono(size: 12, weight: FontWeight.w600),
          ),
        const SizedBox(height: 2),
        Text(
          'Powered by BizBite',
          textAlign: TextAlign.center,
          style: BizBiteTheme.receiptMono(size: 11, color: _inkMuted),
        ),
      ],
    );
  }

  /// 32-col 72mm rule — same `--------------------------------` separator the
  /// Laravel `#thermal-paper` block prints between every section.
  Widget _rule() {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Text(
        '--------------------------------',
        maxLines: 1,
        style: BizBiteTheme.receiptMono(size: 12, color: _ink),
      ),
    );
  }

  /// Bottom dock — Print lives here (below the paper), exactly like the
  /// web preview's top `🖨 Print Bill` button: verify the Laravel template
  /// on paper first, then send it to Bluetooth.
  Widget _footer() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
      child: Row(
        children: [
          Expanded(
            child: FilledButton.tonalIcon(
              onPressed: _printing ? null : _reprint,
              style: FilledButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: _ink,
                disabledBackgroundColor: Colors.white24,
                minimumSize: const Size(0, 54),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
              ),
              icon: _printing
                  ? SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                        strokeWidth: 2.2,
                        color: _ink,
                      ),
                    )
                  : const Icon(Icons.bluetooth_rounded, size: 19),
              label: const Text(
                'Print Bill via Bluetooth',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: FilledButton.icon(
              onPressed: () => widget.orderFlow.clearReceipt(),
              style: FilledButton.styleFrom(
                backgroundColor: BizBiteTheme.brand,
                minimumSize: const Size(0, 54),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
              ),
              icon: const Icon(Icons.add_rounded, size: 19),
              label: const Text(
                'New Order',
                style: TextStyle(fontSize: 14.5, fontWeight: FontWeight.w800),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
