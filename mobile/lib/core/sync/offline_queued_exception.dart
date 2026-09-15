// Raised when a write could not reach the server and was parked in the
// device's durable mutation outbox instead.
//
// Callers MUST treat this as SUCCESS, never as an error dialog: the change
// is already applied to the local SQLite cache, the cashier is told it was
// saved offline, and `SyncOrchestrator` replays it FIFO once the outlet
// Wi-Fi is back.
//
// Bills additionally carry [localBillNo] / [idempotencyKey] so the POS can
// print a LOCAL-XXXX receipt immediately (see `OrderCheckout`).
import '../network/api_exception.dart';

class OfflineQueuedException implements Exception {
  OfflineQueuedException({
    this.label = 'Change',
    this.localBillNo = '',
    this.idempotencyKey = '',
    this.cause,
  });

  /// Short cashier-facing noun for the queued change ("Table update",
  /// "Shift open", "Menu item", …) used in [message].
  final String label;

  /// `LOCAL-XXXXXXXX` for queued bills; empty for every other mutation.
  final String localBillNo;

  /// RFC-4122 key for queued bills (server dedupe); empty otherwise.
  final String idempotencyKey;

  final ApiException? cause;

  /// True for a queued bill (drives the LOCAL receipt path).
  bool get isBill => localBillNo.isNotEmpty;

  /// Cashier-facing confirmation. Phrased as reassurance, not failure.
  String get message => isBill
      ? 'Bill $localBillNo saved offline — it will sync automatically.'
      : '$label saved offline — it will sync automatically.';

  @override
  String toString() => isBill
      ? 'OfflineQueuedException($localBillNo)'
      : 'OfflineQueuedException($label)';
}
