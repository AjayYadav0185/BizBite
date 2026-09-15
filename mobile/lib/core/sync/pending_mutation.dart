// Durable outbox row for a queued non-bill mutation.
//
// Versions of the POS only queued bills; the queue/shift/console/menu screens
// were online-only. This row lets ANY idempotent write survive a dead link:
// the repository queues the exact verb + path + body, applies the change to
// the local cache immediately, and `SyncOrchestrator` replays it FIFO.
//
// Only idempotent writes are queued (PATCH / PUT / DELETE and unique-keyed
// POSTs). Money movements (refunds, wallet recharge) are NEVER queued — see
// `OpsRepository.refund`.
class PendingMutation {
  const PendingMutation({
    required this.id,
    required this.method,
    required this.path,
    required this.payloadJson,
    required this.label,
    required this.status,
    required this.retryCount,
    required this.nextRetryAt,
    required this.lastError,
    required this.createdAt,
  });

  final int id;

  /// Upper-case HTTP verb: `POST` | `PATCH` | `PUT` | `DELETE`.
  final String method;

  /// API-relative path (e.g. `/orders/41/status`).
  final String path;

  /// JSON-encoded request body (`null` → `"null"` for bodyless DELETE).
  final String payloadJson;

  /// Cashier-facing noun shown in the failure list ("Shift open · ₹500").
  final String label;

  final String status; // pending | syncing | failed
  final int retryCount;
  final int nextRetryAt;
  final String lastError;
  final int createdAt;

  bool get isDue =>
      status == 'pending' &&
      nextRetryAt <= DateTime.now().millisecondsSinceEpoch;

  factory PendingMutation.fromMap(Map<String, Object?> map) => PendingMutation(
        id: (map['id'] as num).toInt(),
        method: (map['method'] ?? 'PATCH').toString(),
        path: (map['path'] ?? '').toString(),
        payloadJson: (map['payload_json'] ?? 'null').toString(),
        label: (map['label'] ?? 'Change').toString(),
        status: (map['status'] ?? 'pending').toString(),
        retryCount: (map['retry_count'] as num?)?.toInt() ?? 0,
        nextRetryAt: (map['next_retry_at'] as num?)?.toInt() ?? 0,
        lastError: (map['last_error'] ?? '').toString(),
        createdAt: (map['created_at'] as num?)?.toInt() ?? 0,
      );
}
