// Durable outbox row for `POST /api/orders`.
//
// One row = one bill the cashier has already printed. The SyncOrchestrator
// replays rows FIFO with the SAME idempotency_key so the Laravel
// OrderService dedupes instead of double-billing on flaky Wi-Fi.
class PendingOrder {
  const PendingOrder({
    required this.id,
    required this.idempotencyKey,
    required this.payloadJson,
    required this.localBillNo,
    required this.status,
    required this.retryCount,
    required this.nextRetryAt,
    required this.lastError,
    required this.createdAt,
  });

  final int id;
  final String idempotencyKey;
  final String payloadJson;
  final String localBillNo;
  final String status; // pending | syncing | failed
  final int retryCount;
  final int nextRetryAt;
  final String lastError;
  final int createdAt;

  bool get isDue =>
      status == 'pending' &&
      nextRetryAt <= DateTime.now().millisecondsSinceEpoch;

  factory PendingOrder.fromMap(Map<String, Object?> map) => PendingOrder(
        id: (map['id'] as num).toInt(),
        idempotencyKey: (map['idempotency_key'] ?? '').toString(),
        payloadJson: (map['payload_json'] ?? '{}').toString(),
        localBillNo: (map['local_bill_no'] ?? '').toString(),
        status: (map['status'] ?? 'pending').toString(),
        retryCount: (map['retry_count'] as num?)?.toInt() ?? 0,
        nextRetryAt: (map['next_retry_at'] as num?)?.toInt() ?? 0,
        lastError: (map['last_error'] ?? '').toString(),
        createdAt: (map['created_at'] as num?)?.toInt() ?? 0,
      );
}
