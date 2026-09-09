import 'package:flutter/material.dart';

import '../../features/auth/session_controller.dart';
import '../../features/orders/data/models/order_models.dart';
import '../../features/orders/data/models/order_receipt_model.dart';
import '../../features/orders/order_flow_controller.dart';
import '../services/receipt_printer.dart';
import '../widgets/amount.dart';

/// Post-settlement screen: full receipt preview + reprint / new bill.
///
/// Backed by the ESC/POS payload mirrored from the Laravel `OrderReceipt`, so
/// the on-screen preview and the Bluetooth thermal printout show the same
/// store branding and totals as the web POS print block.
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
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final receipt = widget.receipt;

    return Scaffold(
      appBar: AppBar(
        title: Text('Bill settled'),
        actions: [
          IconButton(
            icon: Icon(_printing ? Icons.hourglass_full : Icons.print, size: 20),
            tooltip: 'Print receipt',
            onPressed: _reprint,
          ),
        ],
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.check_circle, size: 28, color: scheme.primary),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text('Payment ${receipt.paymentMode.label}',
                          style: theme.textTheme.titleMedium),
                    ),
                  ],
                ),
                SizedBox(height: 4),
                Text('Bill #${receipt.orderNumber}',
                    style: theme.textTheme.titleSmall),
                Text('${receipt.placedAt} · ${receipt.cashier}',
                    style: theme.textTheme.bodySmall,
                    selectionColor: scheme.onSurfaceVariant),
                if (_printStatus.isNotEmpty)
                  Text(_printStatus,
                      style: theme.textTheme.bodySmall,
                      selectionColor: scheme.primary),
              ],
            ),
          ),
          Expanded(
            child: ListView(
              padding: EdgeInsets.all(16),
              children: [
                for (final item in receipt.items)
                  _line(theme, scheme, item),
                SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: Text('TOTAL (${receipt.totalQuantity} items)',
                          style: theme.textTheme.titleSmall),
                    ),
                    Text(inr(receipt.totalAmount),
                        style: theme.textTheme.titleMedium,
                        selectionColor: scheme.primary),
                  ],
                ),
              ],
            ),
          ),
          Padding(
            padding: EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: FilledButton.icon(
                    icon: Icon(Icons.add, size: 18),
                    label: Text('New bill',
                        style: theme.textTheme.titleSmall),
                    onPressed: () => widget.orderFlow.clearReceipt(),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _line(ThemeData theme, ColorScheme scheme, OrderItemModel item) {
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(item.foodItemName, style: theme.textTheme.bodyMedium),
              Text('${item.quantity} × ${inr(item.price)}',
                  style: theme.textTheme.bodySmall,
                  selectionColor: scheme.onSurfaceVariant),
            ],
          ),
        ),
        Text(inr(item.subtotal), style: theme.textTheme.bodyMedium),
      ],
    );
  }
}