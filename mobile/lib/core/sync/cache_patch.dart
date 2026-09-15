// Pure list-patch helpers for the offline cache.
//
// Kept free of sqflite/Flutter imports so the optimistic-update rules can be
// unit-tested without a platform channel: a queued offline edit must show up
// in the grid immediately, and the server-wins pull must be able to replace
// the whole list afterwards.
library;

/// Coerce any decoded JSON value into a `String`-keyed row map.
Map<String, dynamic> cacheRow(Object? value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return value.map((k, v) => MapEntry(k.toString(), v));
  return <String, dynamic>{};
}

/// Immutable patch of [rows]: the FIRST row matching [match] is merged with
/// [patch] (or dropped when [remove] is true).
///
/// Returns the (possibly identical) list plus whether anything changed, so
/// callers can skip the SQLite write entirely on a miss.
({List<Object?> rows, bool changed}) patchCacheList(
  List<Object?> rows, {
  required bool Function(Map<String, dynamic> row) match,
  Map<String, dynamic>? patch,
  bool remove = false,
}) {
  var changed = false;
  final next = <Object?>[];
  for (final entry in rows) {
    final row = cacheRow(entry);
    if (!changed && row.isNotEmpty && match(row)) {
      changed = true;
      if (!remove) {
        next.add({...row, ...?patch});
      }
      continue;
    }
    next.add(entry);
  }
  return (rows: changed ? next : rows, changed: changed);
}

/// Extract the list a cached envelope holds at [listKey].
///
/// `listKey` empty means the cached value IS the list (e.g. a bare array
/// response). Returns null when the shape does not match — a corrupt or
/// legacy entry is simply left alone rather than crashing the sync pass.
List<Object?>? cacheListAt(Object? decoded, String listKey) {
  if (listKey.isEmpty) return decoded is List ? decoded : null;
  if (decoded is Map) {
    final nested = decoded[listKey];
    return nested is List ? nested : null;
  }
  return null;
}

/// Rebuild the cached envelope after [rows] replaced its list.
Object? cacheEnvelopeWith(Object? decoded, String listKey, List<Object?> rows) {
  if (listKey.isEmpty) return rows;
  if (decoded is Map) {
    return {
      for (final entry in decoded.entries) entry.key.toString(): entry.value,
      listKey: rows,
    };
  }
  return rows;
}
