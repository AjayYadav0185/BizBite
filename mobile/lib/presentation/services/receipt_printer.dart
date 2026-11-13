import 'dart:typed_data' show Uint8List;

import 'package:blue_thermal_printer/blue_thermal_printer.dart';
import 'package:esc_pos_utils_plus/esc_pos_utils_plus.dart';

import '../../features/orders/data/models/order_receipt_model.dart';
import '../../features/orders/receipt_document.dart';

/// Sends a bill or Kitchen Order Ticket to a Bluetooth thermal printer.
///
/// Layout comes from [ReceiptDocument] — the same fixed-width lines the
/// on-screen preview renders — so what the cashier sees is what prints.
/// Printing fails soft: the receipt stays on screen if no printer answers.
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

  /// ESC/POS code pages are Latin-only: replace anything else (e.g. ₹,
  /// Devanagari item names) so a single character can't abort the print.
  static String asciiSafe(String text) => text
      .replaceAll('₹', 'Rs.')
      .replaceAll('·', '-')
      .replaceAll('—', '-')
      .replaceAll(RegExp(r'[^\x20-\x7E]'), '?');

  /// Raw ESC/POS bytes for [lines] on a 58mm (32 col) or 80mm (48 col) roll.
  static Future<List<int>> bytesFor(
    List<ReceiptLine> lines, {
    required int columns,
  }) async {
    final profile = await CapabilityProfile.load(name: 'default');
    final generator =
        Generator(columns <= 32 ? PaperSize.mm58 : PaperSize.mm80, profile);
    final bytes = <int>[];

    for (final line in lines) {
      bytes.addAll(generator.text(
        asciiSafe(line.text),
        styles: PosStyles(
          bold: line.bold,
          align: switch (line.align) {
            LineAlign.center => PosAlign.center,
            LineAlign.right => PosAlign.right,
            LineAlign.left => PosAlign.left,
          },
          height: line.large ? PosTextSize.size2 : PosTextSize.size1,
          width: line.large ? PosTextSize.size2 : PosTextSize.size1,
        ),
      ));
    }
    bytes.addAll(generator.feed(3));
    bytes.addAll(generator.cut());
    return bytes;
  }

  /// Print the customer bill. `pendingBytes` lets tests inject a payload.
  Future<bool> printReceipt(
    OrderReceiptModel receipt, {
    int columns = 32,
    List<int>? pendingBytes,
  }) async {
    if (!isReady) await init();
    final bytes = pendingBytes ??
        await bytesFor(ReceiptDocument(columns: columns).bill(receipt),
            columns: columns);
    return _send(bytes);
  }

  /// Print the Kitchen Order Ticket (no prices).
  Future<bool> printKot(
    OrderReceiptModel receipt, {
    int columns = 32,
    Map<String, String> lineNotes = const {},
  }) async {
    if (!isReady) await init();
    final bytes = await bytesFor(
        ReceiptDocument(columns: columns).kot(receipt, lineNotes: lineNotes),
        columns: columns);
    return _send(bytes);
  }

  Future<bool> _send(List<int> bytes) async {
    lastError = '';
    try {
      final printer = BlueThermalPrinter.instance;
      final devices = await printer.getBondedDevices();
      if (devices.isEmpty) {
        lastError =
            'No Bluetooth printer found. Pair the thermal printer in Android settings first.';
        return false;
      }

      // Best-effort: the first bonded device is the counter printer.
      final device = devices[0];
      if (!(await printer.isConnected ?? false)) {
        await printer.connect(device);
      }
      await printer.writeBytes(Uint8List.fromList(bytes));
      await printer.disconnect();
      return true;
    } catch (error) {
      lastError = 'Printer error: $error';
      return false;
    }
  }
}
