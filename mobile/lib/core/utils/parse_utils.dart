/// Defensive JSON coercion helpers.
///
/// The Laravel API returns decimal columns (price, subtotal, total_amount)
/// as strings ("12.50") because of the `decimal:2` casts, while quantities
/// arrive as ints. These helpers accept num, String or null uniformly so the
/// models never crash on schema drift between environments.
library;

double toDouble(dynamic value, {double fallback = 0.0}) {
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value.trim()) ?? fallback;
  return fallback;
}

int toInt(dynamic value, {int fallback = 0}) {
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value.trim()) ?? fallback;
  return fallback;
}

String toNullableString(dynamic value) {
  if (value == null) return '';
  final s = value.toString().trim();
  return s.toLowerCase() == 'null' ? '' : s;
}

Map<String, dynamic> toMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return value.map((k, v) => MapEntry(k.toString(), v));
  return {};
}

List<Map<String, dynamic>> toListOfMaps(dynamic value) {
  if (value is List) {
    return value.map(toMap).toList();
  }
  return const [];
}
