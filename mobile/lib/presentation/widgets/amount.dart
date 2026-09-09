import 'package:intl/intl.dart';

NumberFormat _inr = NumberFormat.currency(locale: 'en_IN', name: 'INR');

/// Format an amount as Indian Rupees for the POS UI, e.g. "₹1,234.50".
String inr(num amount) => _inr.format(amount);

/// Compact line for receipts/cart chips without the ₹ symbol: "1,234.50".
String inrPlain(num amount) =>
    _inr.format(amount).replaceAll('₹', '').trim();