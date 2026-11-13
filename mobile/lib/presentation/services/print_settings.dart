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
  static const String paperKey = 'bizbite.print.paper_58mm.v1';
  static const String kotKey = 'bizbite.print.auto_kot.v1';

  final SharedPreferences? _prefs;

  /// True → show receipt preview + manual Print button.
  /// False → skip preview, auto-print in the background.
  bool previewBeforePrint = true;

  /// Paper roll: 58mm (32 columns, most Bluetooth printers) or 80mm (48).
  bool paper58mm = true;

  /// Print a Kitchen Order Ticket automatically after every bill.
  bool autoPrintKot = false;

  /// Characters per line for the selected roll (ESC/POS Font A).
  int get columns => paper58mm ? 32 : 48;

  /// True once the persisted value (if any) has been read.
  bool loaded = false;

  Future<void> _load() async {
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      if (p.containsKey(storeKey)) {
        previewBeforePrint = p.getBool(storeKey) ?? true;
      }
      paper58mm = p.getBool(paperKey) ?? true;
      autoPrintKot = p.getBool(kotKey) ?? false;
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

  Future<void> setPaper58mm(bool value) async {
    if (paper58mm == value) return;
    paper58mm = value;
    notifyListeners();
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.setBool(paperKey, value);
    } catch (_) {}
  }

  Future<void> setAutoPrintKot(bool value) async {
    if (autoPrintKot == value) return;
    autoPrintKot = value;
    notifyListeners();
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.setBool(kotKey, value);
    } catch (_) {}
  }
}
