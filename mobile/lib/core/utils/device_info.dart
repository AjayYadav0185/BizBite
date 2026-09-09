import 'dart:math';

import 'package:flutter/foundation.dart' show defaultTargetPlatform;

/// Identifiers sent with `POST /api/login` so the Laravel side can bind the
/// Flutter device to the store (`StoreDevice::updateOrCreate`), track app
/// versions and scope push notifications.
class DeviceInfo {
  static String? _deviceId;

  /// Stable for the lifetime of the app process; regenerated on relaunch.
  /// The backend upserts StoreDevice rows keyed by store + device_id, so a
  /// fresh id simply re-registers this device — never a hard failure.
  static String get deviceId {
    _deviceId ??= _generateHexId();
    return _deviceId!;
  }

  /// Lower-case target platform name ("android" / "ios" / "macos" / ...).
  static String get platform => defaultTargetPlatform.name;

  /// Keep in sync with `version:` in pubspec.yaml.
  static const String appVersion = '1.0.0';

  /// Fresh v4-style UUID used as the order idempotency key. The server
  /// dedupes retries on this value, so exact RFC shape is not required.
  static String newIdempotencyKey() {
    final random = Random.secure();
    final bytes = List<int>.generate(16, (_) => random.nextInt(256));

    return '${_hex(bytes, 0, 4)}-${_hex(bytes, 4, 2)}-4${_hex(bytes, 6, 1)}-'
        '${_hex(bytes, 7, 1)}-${_hex(bytes, 8, 8)}';
  }

  static String _generateHexId() {
    final random = Random.secure();
    final bytes = List<int>.generate(16, (_) => random.nextInt(256));
    return _hex(bytes, 0, 16);
  }

  static String _hex(List<int> bytes, int start, int count) {
    final buffer = StringBuffer();
    for (var i = start; i < start + count; i++) {
      buffer.write(bytes[i].toRadixString(16).padLeft(2, '0'));
    }
    return buffer.toString();
  }
}