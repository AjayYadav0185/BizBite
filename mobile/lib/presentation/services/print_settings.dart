import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Till-level printing preference: preview the receipt before printing.
///
///  - ON (default)  → after `POST /api/orders` the app shows the
///    [ReceiptScreen] preview; the cashier taps **Print Bill** explicitly.
///  - OFF           → the preview is skipped: the bill is sent straight to
///    the Bluetooth thermal printer and the POS stays on the billing grid
///    for the next customer (fast rush-hour mode).
///
/// Persisted in SharedPreferences (not the secure vault — this is a UI
/// preference, not a secret) so it survives cold starts. Notified via
/// [ChangeNotifier] so the Profile toggle rebuilds instantly.
class PrintSettings with ChangeNotifier {
  // ignore: prefer_initializing_formals
  PrintSettings({SharedPreferences? prefs}) : _prefs = prefs {
    _load();
  }

  static const String storeKey = 'bizbite.print.preview_before_print.v1';

  final SharedPreferences? _prefs;

  /// True → show receipt preview + manual Print button.
  /// False → skip preview, auto-print in the background.
  bool previewBeforePrint = true;

  /// True once the persisted value (if any) has been read.
  bool loaded = false;

  Future<void> _load() async {
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      if (p.containsKey(storeKey)) {
        previewBeforePrint = p.getBool(storeKey) ?? true;
      }
      loaded = true;
      notifyListeners();
    } catch (_) {
      // Corrupt / unavailable prefs must never block the till — stay on the
      // safe default (preview ON).
    }
  }

  Future<void> setPreviewBeforePrint(bool value) async {
    if (previewBeforePrint == value) return;
    previewBeforePrint = value;
    notifyListeners();
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.setBool(storeKey, value);
    } catch (_) {
      // Persist failure is non-fatal; the in-memory value still applies for
      // this session.
    }
  }
}
