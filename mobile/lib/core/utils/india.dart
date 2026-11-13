/// India-specific helpers — the Dart twin of `App\Support\India` (Laravel).
///
/// Keep the two in lock-step: GSTIN check digit, mobile rules, rupee
/// round-off and amount-in-words must agree so the phone shows exactly what
/// the server saves and prints.
abstract final class India {
  static const String sacRestaurant = '996331';

  static final RegExp _gstinPattern =
      RegExp(r'^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$');
  static final RegExp fssaiPattern = RegExp(r'^[0-9]{14}$');
  static final RegExp vpaPattern =
      RegExp(r'^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z][a-zA-Z0-9.\-]{1,64}$');
  static final RegExp pincodePattern = RegExp(r'^[1-9][0-9]{5}$');
  static final RegExp _mobilePattern = RegExp(r'^[6-9][0-9]{9}$');

  /// GST state codes (first two digits of a GSTIN). NCR states first.
  static const Map<String, String> states = {
    '07': 'Delhi', '06': 'Haryana', '09': 'Uttar Pradesh', '08': 'Rajasthan',
    '01': 'Jammu and Kashmir', '02': 'Himachal Pradesh', '03': 'Punjab',
    '04': 'Chandigarh', '05': 'Uttarakhand', '10': 'Bihar', '11': 'Sikkim',
    '12': 'Arunachal Pradesh', '13': 'Nagaland', '14': 'Manipur',
    '15': 'Mizoram', '16': 'Tripura', '17': 'Meghalaya', '18': 'Assam',
    '19': 'West Bengal', '20': 'Jharkhand', '21': 'Odisha',
    '22': 'Chhattisgarh', '23': 'Madhya Pradesh', '24': 'Gujarat',
    '26': 'Dadra and Nagar Haveli and Daman and Diu', '27': 'Maharashtra',
    '29': 'Karnataka', '30': 'Goa', '31': 'Lakshadweep', '32': 'Kerala',
    '33': 'Tamil Nadu', '34': 'Puducherry',
    '35': 'Andaman and Nicobar Islands', '36': 'Telangana',
    '37': 'Andhra Pradesh', '38': 'Ladakh',
  };

  // ---------------------------------------------------------------------------
  // Money
  // ---------------------------------------------------------------------------

  /// Round to paise (2dp) without binary-float drift (0.1 + 0.2 issues).
  static double paise(double value) => (value * 100).round() / 100;

  /// Nearest-rupee cash rounding — identical to OrderService on the server
  /// (PHP round(): half away from zero).
  static double roundRupee(double value) => value.roundToDouble();

  /// Indian digit grouping without the ₹ sign: 1234567.5 → "12,34,567.50".
  static String number(num value, {int decimals = 2}) {
    final negative = value < 0;
    final fixed = value.abs().toStringAsFixed(decimals);
    final parts = fixed.split('.');
    var whole = parts[0];
    if (whole.length > 3) {
      final last3 = whole.substring(whole.length - 3);
      var rest = whole.substring(0, whole.length - 3);
      final groups = <String>[];
      while (rest.length > 2) {
        groups.insert(0, rest.substring(rest.length - 2));
        rest = rest.substring(0, rest.length - 2);
      }
      if (rest.isNotEmpty) groups.insert(0, rest);
      whole = '${groups.join(',')},$last3';
    }
    final out = decimals > 0 ? '$whole.${parts[1]}' : whole;
    return negative ? '-$out' : out;
  }

  /// "Rupees One Thousand Two Hundred Ten and Fifty Paise Only".
  static String words(num amount) {
    final value = paise(amount.abs().toDouble());
    final rupees = value.floor();
    final p = ((value - rupees) * 100).round();
    var text = 'Rupees ${rupees == 0 ? 'Zero' : _spell(rupees)}';
    if (p > 0) text += ' and ${_spell(p)} Paise';
    return '$text Only';
  }

  static const _ones = [
    '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
    'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
    'Seventeen', 'Eighteen', 'Nineteen',
  ];
  static const _tens = [
    '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy',
    'Eighty', 'Ninety',
  ];

  static String _two(int x) =>
      x < 20 ? _ones[x] : '${_tens[x ~/ 10]} ${_ones[x % 10]}'.trim();

  static String _spell(int n) {
    final parts = <String>[];
    for (final (unit, label) in const [
      (10000000, 'Crore'),
      (100000, 'Lakh'),
      (1000, 'Thousand'),
      (100, 'Hundred'),
    ]) {
      if (n >= unit) {
        final count = n ~/ unit;
        parts.add('${unit == 10000000 ? _spell(count) : _two(count)} $label');
        n %= unit;
      }
    }
    if (n > 0) parts.add(_two(n));
    return parts.join(' ');
  }

  // ---------------------------------------------------------------------------
  // Validation
  // ---------------------------------------------------------------------------

  /// Format + state code + check digit (catches typos in a GSTIN).
  static bool isValidGstin(String? raw) {
    final gstin = (raw ?? '').trim().toUpperCase();
    if (!_gstinPattern.hasMatch(gstin)) return false;
    if (!states.containsKey(gstin.substring(0, 2))) return false;

    const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    var sum = 0;
    for (var i = 0; i < 14; i++) {
      final product = chars.indexOf(gstin[i]) * ((i % 2) + 1);
      sum += product ~/ 36 + product % 36;
    }
    return chars[(36 - sum % 36) % 36] == gstin[14];
  }

  static String? stateFromGstin(String? gstin) {
    final g = (gstin ?? '').trim();
    return g.length >= 2 ? states[g.substring(0, 2)] : null;
  }

  /// Bare 10-digit Indian mobile (strips +91 / 0 / spaces), or null.
  static String? normaliseMobile(String? phone) {
    var digits = (phone ?? '').replaceAll(RegExp(r'\D'), '');
    if (digits.length == 12 && digits.startsWith('91')) {
      digits = digits.substring(2);
    } else if (digits.length == 11 && digits.startsWith('0')) {
      digits = digits.substring(1);
    }
    return _mobilePattern.hasMatch(digits) ? digits : null;
  }

  // ---------------------------------------------------------------------------
  // UPI / WhatsApp
  // ---------------------------------------------------------------------------

  /// upi://pay deep link for a dynamic QR with a fixed amount.
  static String upiLink({
    required String vpa,
    required String payeeName,
    required double amount,
    String? note,
  }) {
    String enc(String v) => Uri.encodeComponent(v);
    final name = payeeName.length > 40 ? payeeName.substring(0, 40) : payeeName;
    final buffer = StringBuffer('upi://pay?pa=${vpa.trim()}')
      ..write('&pn=${enc(name)}')
      ..write('&am=${amount.toStringAsFixed(2)}')
      ..write('&cu=INR');
    if (note != null && note.isNotEmpty) buffer.write('&tn=${enc(note)}');
    return buffer.toString();
  }

  /// wa.me link — opens WhatsApp with the text, addressed to [mobile] when
  /// it is a valid Indian number (otherwise WhatsApp asks for a contact).
  static Uri whatsappUri(String text, {String? mobile}) {
    final m = normaliseMobile(mobile);
    return Uri.parse(
        'https://wa.me/${m == null ? '' : '91$m'}?text=${Uri.encodeComponent(text)}');
  }
}
