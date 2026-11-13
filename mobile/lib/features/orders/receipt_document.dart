import '../../core/utils/india.dart';
import 'data/models/order_models.dart';
import 'data/models/order_receipt_model.dart';

enum LineAlign { left, center, right }

/// One printed line. [text] is already laid out to the paper width
/// (left/right pairs padded with spaces), so the screen preview and the
/// ESC/POS printer show exactly the same characters.
class ReceiptLine {
  const ReceiptLine(
    this.text, {
    this.align = LineAlign.left,
    this.bold = false,
    this.large = false,
  });

  final String text;
  final LineAlign align;
  final bool bold;

  /// Double-height emphasis (store name, grand total, KOT header).
  final bool large;
}

/// Builds the thermal bill and the Kitchen Order Ticket as plain fixed-width
/// lines — the ONE template behind the on-screen receipt preview and the
/// Bluetooth ESC/POS print, mirroring `resources/views/partials/receipt.blade.php`
/// on the web:
///
///  * GST registered store → "TAX INVOICE" with GSTIN, CGST/SGST per slab,
///    SAC 996331, round-off and amount in words.
///  * Not registered → "BILL OF SUPPLY" with no tax lines.
///  * FSSAI licence number whenever the store has one.
class ReceiptDocument {
  ReceiptDocument({this.columns = 32}) : assert(columns >= 24);

  /// Characters per line: 32 for 58mm rolls, 48 for 80mm.
  final int columns;

  String get rule => '-' * columns;

  String _money(num v) => India.number(v);

  /// "Left ........ Right" padded to the paper width (wraps when too long).
  List<String> _pair(String left, String right) {
    final gap = columns - left.length - right.length;
    if (gap >= 1) return ['$left${' ' * gap}$right'];
    return [..._wrap(left), right.padLeft(columns)];
  }

  List<String> _wrap(String text, {int? width}) {
    final w = width ?? columns;
    final words = text.split(RegExp(r'\s+')).where((s) => s.isNotEmpty);
    final out = <String>[];
    var line = '';
    for (var word in words) {
      while (word.length > w) {
        if (line.isNotEmpty) {
          out.add(line);
          line = '';
        }
        out.add(word.substring(0, w));
        word = word.substring(w);
      }
      if (line.isEmpty) {
        line = word;
      } else if (line.length + 1 + word.length <= w) {
        line = '$line $word';
      } else {
        out.add(line);
        line = word;
      }
    }
    if (line.isNotEmpty) out.add(line);
    return out.isEmpty ? [''] : out;
  }

  List<ReceiptLine> _center(String text, {bool bold = false, bool large = false}) =>
      _wrap(text, width: large ? columns ~/ 2 : columns)
          .map((l) => ReceiptLine(l, align: LineAlign.center, bold: bold, large: large))
          .toList();

  List<ReceiptLine> _left(String text, {bool bold = false}) =>
      _wrap(text).map((l) => ReceiptLine(l, bold: bold)).toList();

  List<ReceiptLine> _pairLines(String l, String r, {bool bold = false}) =>
      _pair(l, r).map((t) => ReceiptLine(t, bold: bold)).toList();

  /// Item table: name on its own line(s) when it doesn't fit, then
  /// qty / rate / amount right-aligned in fixed columns.
  List<ReceiptLine> _itemRows(OrderReceiptModel r) {
    const qtyW = 4;
    final amtW = columns >= 40 ? 11 : 9;
    final rateW = columns >= 40 ? 10 : 8;
    final nameW = columns - qtyW - rateW - amtW;

    String row(String name, String qty, String rate, String amt) =>
        name.padRight(nameW) +
        qty.padLeft(qtyW) +
        rate.padLeft(rateW) +
        amt.padLeft(amtW);

    final lines = <ReceiptLine>[
      ReceiptLine(row('Item', 'Qty', 'Rate', 'Amt'), bold: true),
      ReceiptLine(rule),
    ];
    for (final item in r.items) {
      final nameParts = _wrap(item.foodItemName, width: nameW);
      final nums = [
        '${item.quantity}',
        _money(item.price),
        _money(item.subtotal),
      ];
      if (nameParts.length == 1) {
        lines.add(ReceiptLine(row(nameParts.first, nums[0], nums[1], nums[2])));
      } else {
        for (final part in nameParts.take(nameParts.length - 1)) {
          lines.add(ReceiptLine(part));
        }
        lines.add(ReceiptLine(row(nameParts.last, nums[0], nums[1], nums[2])));
      }
    }
    return lines;
  }

  /// The customer bill / GST tax invoice.
  List<ReceiptLine> bill(OrderReceiptModel r) {
    final s = r.store;
    final gst = s.printsTaxInvoice;
    final lines = <ReceiptLine>[];

    if (s.printHeader.isNotEmpty) {
      lines.addAll(_center(s.printHeader.toUpperCase(), bold: true));
    }
    lines.addAll(_center(s.name.isEmpty ? 'BIZBITE' : s.name.toUpperCase(),
        bold: true, large: true));
    if (s.fullAddress.isNotEmpty) lines.addAll(_center(s.fullAddress));
    if (s.phone.isNotEmpty) lines.addAll(_center('Ph: ${s.phone}'));
    if (gst) lines.addAll(_center('GSTIN: ${s.gstin}', bold: true));
    if (s.fssai.isNotEmpty) lines.addAll(_center('FSSAI Lic. No: ${s.fssai}'));
    lines.addAll(_center(gst ? 'TAX INVOICE' : 'BILL OF SUPPLY', bold: true));
    if (r.isReprint) lines.addAll(_center('** DUPLICATE COPY **'));
    if (r.isOffline) lines.addAll(_center('** OFFLINE - PENDING SYNC **'));

    lines.add(ReceiptLine(rule));
    lines.addAll(_pairLines('Bill No:', r.orderNumber, bold: true));
    lines.addAll(_pairLines('Date:', r.placedAt));
    final type = r.tableNumber.isNotEmpty
        ? '${r.orderType.label} - Table ${r.tableNumber}'
        : r.orderType.label;
    lines.addAll(_pairLines(type, r.cashier));
    if (r.customerName.isNotEmpty || r.customerPhone.isNotEmpty) {
      final phone = r.customerPhone.isNotEmpty ? ' (${r.customerPhone})' : '';
      lines.addAll(_left('Customer: ${r.customerName}$phone'.trim()));
    }
    if (r.deliveryAddress.isNotEmpty) {
      lines.addAll(_left('Deliver to: ${r.deliveryAddress}'));
    }

    lines.add(ReceiptLine(rule));
    lines.addAll(_itemRows(r));
    lines.add(ReceiptLine(rule));
    lines.addAll(_pairLines(
        'Items: ${r.totalQuantity}', 'Sub Total: ${_money(r.grossTotal)}'));
    if (r.discountAmount > 0) {
      final label = r.campaignCode.isNotEmpty
          ? 'Discount (${r.campaignCode})'
          : 'Discount';
      lines.addAll(_pairLines(label, '-${_money(r.discountAmount)}'));
    }

    if (gst && r.taxBreakup.isNotEmpty) {
      lines.add(ReceiptLine(rule));
      final w = (columns - 5) ~/ 3;
      String taxRow(String a, String b, String c, String d) =>
          a.padRight(columns - 3 * w) + b.padLeft(w) + c.padLeft(w) + d.padLeft(w);
      lines.add(ReceiptLine(taxRow('GST%', 'Taxable', 'CGST', 'SGST'), bold: true));
      for (final row in r.taxBreakup) {
        lines.add(ReceiptLine(taxRow('${row.rate}%', _money(row.taxable),
            _money(row.cgst), _money(row.sgst))));
      }
      lines.addAll(_left('Prices incl. GST - SAC ${r.sacCode}'));
    }

    if (r.roundOff != 0) {
      lines.addAll(_pairLines('Round off', r.roundOff.toStringAsFixed(2)));
    }
    lines.add(ReceiptLine(rule));
    lines.addAll(_pair('TOTAL', 'Rs.${_money(r.totalAmount)}')
        .map((t) => ReceiptLine(t, bold: true)));
    lines.addAll(_left(r.words));
    lines.add(ReceiptLine(rule));

    lines.addAll(_pairLines('Paid via', r.paymentMode.label.toUpperCase(), bold: true));
    if (r.tenderedAmount > 0) {
      lines.addAll(_pairLines('Tendered', _money(r.tenderedAmount)));
      lines.addAll(_pairLines('Change', _money(r.changeAmount)));
    }
    if (r.upiRef.isNotEmpty) lines.addAll(_pairLines('UPI Ref', r.upiRef));
    if (r.paymentMode == PaymentMode.credit) {
      lines.addAll(_center('*** PAYMENT DUE (CREDIT) ***', bold: true));
    }
    if (r.notes.isNotEmpty) lines.addAll(_left('Note: ${r.notes}'));

    lines.add(ReceiptLine(rule));
    lines.addAll(_center(
        s.printFooter.isNotEmpty ? s.printFooter : 'Thank you! Visit again.'));
    lines.addAll(_center('Powered by BizBite POS'));
    return lines;
  }

  /// Kitchen Order Ticket — no prices, big quantities, notes highlighted.
  /// [lineNotes] are the per-item kitchen notes kept on the device.
  List<ReceiptLine> kot(OrderReceiptModel r, {Map<String, String> lineNotes = const {}}) {
    final lines = <ReceiptLine>[
      ..._center('KOT', bold: true, large: true),
      ..._center(
        r.tableNumber.isNotEmpty
            ? '${r.orderType.label.toUpperCase()} - TABLE ${r.tableNumber}'
            : r.orderType.label.toUpperCase(),
        bold: true,
      ),
      ReceiptLine(rule),
      ..._pairLines('#${r.orderNumber}', r.placedAt),
      if (r.cashier.isNotEmpty) ..._left('By: ${r.cashier}'),
      ReceiptLine(rule),
    ];
    for (final item in r.items) {
      final qty = '${item.quantity} x '.padLeft(6);
      final parts = _wrap(item.foodItemName, width: columns - qty.length);
      lines.add(ReceiptLine('$qty${parts.first}', bold: true));
      for (final more in parts.skip(1)) {
        lines.add(ReceiptLine('${' ' * qty.length}$more', bold: true));
      }
      final note = lineNotes[item.foodItemName];
      if (note != null && note.isNotEmpty) {
        lines.addAll(_left('${' ' * qty.length}>> $note'));
      }
    }
    lines.add(ReceiptLine(rule));
    if (r.notes.isNotEmpty) {
      lines.addAll(_left('NOTE: ${r.notes}', bold: true));
      lines.add(ReceiptLine(rule));
    }
    lines.addAll(_center('Total items: ${r.totalQuantity}'));
    return lines;
  }

  /// Plain-text bill for WhatsApp sharing.
  static String whatsappText(OrderReceiptModel r) {
    final b = StringBuffer()
      ..writeln('*${r.store.name.isEmpty ? 'BizBite' : r.store.name}*')
      ..writeln('Bill: ${r.orderNumber} · ${r.placedAt}')
      ..writeln();
    for (final item in r.items) {
      b.writeln('${item.quantity} x ${item.foodItemName} — ₹${India.number(item.subtotal)}');
    }
    if (r.discountAmount > 0) {
      b.writeln('Discount: -₹${India.number(r.discountAmount)}');
    }
    b.writeln('*Total: ₹${India.number(r.totalAmount)}* (${r.paymentMode.label.toUpperCase()})');
    if (r.store.printsTaxInvoice) b.writeln('GSTIN: ${r.store.gstin}');
    b
      ..writeln()
      ..write(r.store.printFooter.isNotEmpty
          ? r.store.printFooter
          : 'Thank you! Visit again.');
    return b.toString();
  }
}
