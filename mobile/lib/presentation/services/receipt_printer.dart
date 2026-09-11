import 'dart:typed_data' show Uint8List;

import 'package:blue_thermal_printer/blue_thermal_printer.dart';
import 'package:esc_pos_utils_plus/esc_pos_utils_plus.dart';

import '../../features/orders/data/models/order_models.dart';
import '../../features/orders/data/models/order_receipt_model.dart';

/// Renders an [OrderReceiptModel] to raw ESC/POS bytes and pushes them to a
/// Bluetooth thermal printer.
///
/// Byte-for-byte mirror of the Laravel `#thermal-paper` block
/// (`resources/views/livewire/pos/billing-dashboard.blade.php`): same line
/// order, same 32-col `--------------------------------` rules, same
/// `<qty> x <name>` rows, same owner template lines (`print_header` /
/// `print_footer` from the web Receipt Customizer). Mirrors the browser
/// "print receipt" step of the Livewire POS: identical store branding,
/// itemized rows and totals appear on the mobile till. If no printer is
/// reachable the receipt is still available on-screen — printing fails
/// soft and never blocks the billing flow.
class ReceiptPrinter {
  /// True when the thermal generator is ready (ESC/POS profile loaded).
  bool isReady = false;

  /// Cashier-readable message of the last print attempt ('' when fine).
  String lastError = '';

  Future<void> init() async {
    try {
      await CapabilityProfile.ensureProfileLoaded();
      isReady = true;
    } catch (_) {
      isReady = false;
    }
  }

  /// Build the raw ESC/POS byte stream for a settled receipt.
  ///
  /// Same template as the Laravel `#thermal-paper` block: header template →
  /// store name/address/phone → 32-col rule → `Bill: <n>` + date →
  /// cashier → rule → `<qty> x <name>` rows → rule → TOTAL / Paid-via →
  /// rule → footer template → branding.
  Future<List<int>> _buildBytes(OrderReceiptModel receipt) async {
    final profile = await CapabilityProfile.load(name: 'default');
    final generator = Generator(PaperSize.mm80, profile);

    final bytes = <int>[];

    void text(
      String line, {
      bool bold = false,
      PosAlign align = PosAlign.left,
      PosTextSize size = PosTextSize.size1,
    }) {
      bytes.addAll(
        generator.text(
          line,
          styles: PosStyles(
            bold: bold,
            align: align,
            height: size,
            width: size,
          ),
          linesAfter: 0,
        ),
      );
    }

    void divider() => text('--------------------------------');

    // --- Header: Laravel owner template lines (Receipt Customizer) --------
    if (receipt.store.printHeader.isNotEmpty) {
      text(
        receipt.store.printHeader.toUpperCase(),
        bold: true,
        align: PosAlign.center,
      );
    }
    text(receipt.store.name.toUpperCase(), bold: true, align: PosAlign.center);
    if (receipt.store.address.isNotEmpty) {
      text(receipt.store.address, align: PosAlign.center);
    }
    if (receipt.store.phone.isNotEmpty) {
      text('Ph: ${receipt.store.phone}', align: PosAlign.center);
    }
    divider();

    // --- Bill meta: `Bill: <n>` + date row, cashier below (web shape) ------
    text('Bill: ${receipt.orderNumber}  ${receipt.placedAt}');
    if (receipt.cashier.isNotEmpty) {
      text('Cashier: ${receipt.cashier}');
    }
    divider();

    // --- Items: `<qty> x <name>` … `<subtotal>` (web shape) ----------------
    for (final item in receipt.items) {
      text('${item.quantity} x ${item.foodItemName}');
      text('  ${item.subtotal.toStringAsFixed(2)}', align: PosAlign.right);
    }
    divider();

    // --- Totals: TOTAL / Paid-via rows (web shape) -------------------------
    text('TOTAL  Rs. ${receipt.totalAmount.toStringAsFixed(2)}', bold: true);
    text('Paid via  ${receipt.paymentMode.label.toUpperCase()}');
    divider();

    // --- Footer: Laravel owner template lines (Receipt Customizer) ---------
    if (receipt.store.printFooter.isNotEmpty) {
      text(receipt.store.printFooter, align: PosAlign.center);
    }
    text('Powered by BizBite', align: PosAlign.center);

    bytes.addAll(generator.feed(3));
    bytes.addAll(generator.cut());

    return bytes;
  }

  /// Print a receipt over Bluetooth. Returns true on success.
  ///
  /// `pendingBytes` can override the generated payload (tests inject a fake).
  Future<bool> printReceipt(
    OrderReceiptModel receipt, {
    List<int>? pendingBytes,
  }) async {
    lastError = '';
    if (!isReady) {
      await init();
    }

    final bytes = pendingBytes ?? await _buildBytes(receipt);

    try {
      final printer = BlueThermalPrinter.instance;
      final devices = await printer.getBondedDevices();
      if (devices.isEmpty) {
        lastError =
            'No Bluetooth printer found. Pair the thermal printer first.';
        return false;
      }

      // Best-effort: pick the first bonded device.
      final device = devices[0];
      await printer.connect(device);
      await printer.writeBytes(Uint8List.fromList(bytes));
      await printer.disconnect();
      return true;
    } catch (error) {
      lastError = 'Printer error: $error';
      return false;
    }
  }
}
