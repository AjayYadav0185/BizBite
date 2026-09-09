import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../features/auth/session_controller.dart';
import '../../features/orders/data/models/order_models.dart';
import '../../features/orders/data/models/order_receipt_model.dart';
import '../../features/orders/order_flow_controller.dart';
import '../services/receipt_printer.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/thermal_paper.dart';

/// Screen 3 — Receipt Preview (thermal-printer optimized).
///
/// A digital preview panel shaped like a 58mm/80mm thermal paper roll:
/// jagged tear edges top & bottom, monospaced high-contrast typography,
/// dashed perforation dividers, an itemized table, a bold total and a QR
/// code — floating on a dark "printer bed" so the paper pops. The footer
/// holds the two primary actions: "Print Bill via Bluetooth" and
/// "New Order".
///
/// Backed by the ESC/POS payload mirrored from the Laravel `OrderReceipt`,
/// so the on-screen preview and the Bluetooth thermal printout show the
/// same store branding and totals as the web POS print block.
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

  /// On-paper ink colors (warm near-black + muted grey).
  static const Color _ink = Color(0xFF26221E);
  static const Color _inkMuted = Color(0xFF8A857F);

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
      // Dark "printer bed" background — makes the paper roll the hero.
      backgroundColor: const Color(0xFF161310),
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
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          const Icon(Icons.check_circle_rounded,
              size: 28, color: BizBiteTheme.success),
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
                      color: Color(0xFF1B5E20)),
                ),
                const SizedBox(height: 2),
                Text(
                  '${widget.receipt.paymentMode.label} · Bill #${widget.receipt.orderNumber}',
                  style: const TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF33691E)),
                ),
                if (_printStatus.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    _printStatus,
                    style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                        color: Color(0xFF33691E)),
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

  /// Monospaced receipt body — mirrors the ESC/POS bytes produced by
  /// [ReceiptPrinter] so screen and paper look identical.
  Widget _receiptContent() {
    final receipt = widget.receipt;
    final store = receipt.store;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // --- Store branding -------------------------------------------------
        if (store.printHeader.isNotEmpty)
          Text(
            store.printHeader,
            textAlign: TextAlign.center,
            style: BizBiteTheme.receiptMono(
                size: 12.5, weight: FontWeight.w800, letterSpacing: 2),
          ),
        Text(
          store.name,
          textAlign: TextAlign.center,
          style: BizBiteTheme.receiptMono(size: 17, weight: FontWeight.w800),
        ),
        if (store.address.isNotEmpty)
          Text(
            store.address,
            textAlign: TextAlign.center,
            style: BizBiteTheme.receiptMono(size: 11, color: _inkMuted),
          ),
        if (store.phone.isNotEmpty)
          Text(
            'Ph: ${store.phone}',
            textAlign: TextAlign.center,
            style: BizBiteTheme.receiptMono(size: 11, color: _inkMuted),
          ),
        const DashedDivider(),
        // --- Bill meta --------------------------------------------------------
        _metaRow('Bill #', receipt.orderNumber, bold: true),
        _metaRow('Date', receipt.placedAt),
        _metaRow('Cashier', receipt.cashier),
        _metaRow('Order', receipt.orderType.label),
        if (receipt.customerName.isNotEmpty)
          _metaRow('Customer', receipt.customerName),
        if (receipt.upiRef.isNotEmpty) _metaRow('UPI Ref', receipt.upiRef),
        const DashedDivider(),
        // --- Items ------------------------------------------------------------
        Text(
          'ITEM                QTY   AMOUNT',
          style: BizBiteTheme.receiptMono(
              size: 10.5, weight: FontWeight.w800, color: _inkMuted),
        ),
        const SizedBox(height: 6),
        for (final item in receipt.items)
          Padding(
            padding: const EdgeInsets.only(bottom: 7),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    item.foodItemName,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: BizBiteTheme.receiptMono(
                        size: 12.5, weight: FontWeight.w600),
                  ),
                ),
                Text(
                  '${item.quantity}x',
                  style: BizBiteTheme.receiptMono(size: 12.5),
                ),
                const SizedBox(width: 4),
                SizedBox(
                  width: 62,
                  child: Text(
                    'Rs.${item.subtotal.toStringAsFixed(2)}',
                    textAlign: TextAlign.end,
                    style: BizBiteTheme.receiptMono(
                        size: 12.5, weight: FontWeight.w700),
                  ),
                ),
              ],
            ),
          ),
        const DashedDivider(),
        // --- Totals -------------------------------------------------------------
        Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Expanded(
              child: Text(
                'TOTAL (${receipt.totalQuantity} items)',
                style: BizBiteTheme.receiptMono(
                    size: 12.5, weight: FontWeight.w800),
              ),
            ),
            Text(
              'Rs.${receipt.totalAmount.toStringAsFixed(2)}',
              style:
                  BizBiteTheme.receiptMono(size: 19, weight: FontWeight.w800),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Row(
          children: [
            Expanded(
              child: Text(
                'PAID VIA',
                style: BizBiteTheme.receiptMono(size: 10.5, color: _inkMuted),
              ),
            ),
            Text(
              receipt.paymentMode.label,
              style:
                  BizBiteTheme.receiptMono(size: 12, weight: FontWeight.w800),
            ),
          ],
        ),
        const SizedBox(height: 14),
        // --- QR verification code --------------------------------------------
        Center(
          child: QrImageView(
            data:
                'BIZBITE|${receipt.orderNumber}|${receipt.totalAmount.toStringAsFixed(2)}',
            version: QrVersions.auto,
            size: 92,
            padding: EdgeInsets.zero,
            eyeStyle:
                const QrEyeStyle(eyeShape: QrEyeShape.square, color: _ink),
            dataModuleStyle: const QrDataModuleStyle(
                dataModuleShape: QrDataModuleShape.square, color: _ink),
          ),
        ),
        const SizedBox(height: 6),
        Text(
          'Scan to verify · ${receipt.orderNumber}',
          textAlign: TextAlign.center,
          style: BizBiteTheme.receiptMono(size: 10, color: _inkMuted),
        ),
        const SizedBox(height: 12),
        if (store.printFooter.isNotEmpty)
          Text(
            store.printFooter,
            textAlign: TextAlign.center,
            style: BizBiteTheme.receiptMono(size: 12, weight: FontWeight.w800),
          ),
        Text(
          'Powered by BizBite',
          textAlign: TextAlign.center,
          style: BizBiteTheme.receiptMono(size: 10, color: _inkMuted),
        ),
      ],
    );
  }

  Widget _metaRow(String label, String value, {bool bold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 1.5),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '$label: ',
            style: BizBiteTheme.receiptMono(size: 11.5, color: _inkMuted),
          ),
          Expanded(
            child: Text(
              value,
              style: BizBiteTheme.receiptMono(
                  size: 11.5,
                  weight: bold ? FontWeight.w800 : FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }

  /// Footer — the two primary post-settlement actions.
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
                    borderRadius: BorderRadius.circular(16)),
              ),
              icon: _printing
                  ? SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                          strokeWidth: 2.2, color: _ink),
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
                    borderRadius: BorderRadius.circular(16)),
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