import 'package:dio/dio.dart';

import '../../../../core/config/api_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/sync/outbox_dao.dart';
import '../../../../core/utils/parse_utils.dart';
import '../models/order_models.dart';
import '../models/order_receipt_model.dart';

/// Offline-first order gateway.
///
/// Online: posts straight through and returns the server receipt.
/// Offline (or timeout): persists the exact wire payload into the durable
/// `pending_orders` outbox with the SAME idempotency key and throws an
/// [OfflineQueuedException] carrying the local bill number — the POS prints
/// immediately and SyncOrchestrator replays later with zero duplicates.
class OrderRepository {
  OrderRepository({required this._client, OutboxDao? outbox})
      : _outbox = outbox ?? OutboxDao();

  final DioClient _client;
  final OutboxDao _outbox;

  OutboxDao get outbox => _outbox;

  /// Place an order. Returns the server-computed receipt (201 Created).
  Future<OrderReceiptModel> place(PlaceOrderRequest request) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.orders,
        data: request.toJson(),
      );
      return OrderReceiptModel.fromJson(toMap(response.data));
    } on DioException catch (error) {
      if (_isOffline(error)) {
        await _outbox.enqueue(
          idempotencyKey: request.idempotencyKey,
          payloadJson: _encode(request.toJson()),
          localBillNo: _localBillNo(request.idempotencyKey),
        );
        throw OfflineQueuedException(
          localBillNo: _localBillNo(request.idempotencyKey),
          idempotencyKey: request.idempotencyKey,
          cause: apiExceptionFrom(error),
        );
      }
      throw apiExceptionFrom(error);
    }
  }

  bool _isOffline(DioException error) =>
      error.type == DioExceptionType.connectionError ||
      error.type == DioExceptionType.connectionTimeout ||
      error.type == DioExceptionType.sendTimeout ||
      error.type == DioExceptionType.receiveTimeout ||
      error.type == DioExceptionType.unknown;

  static String _localBillNo(String key) =>
      'LOCAL-${key.replaceAll('-', '').substring(0, 8).toUpperCase()}';

  static String _encode(Map<String, dynamic> json) {
    final buffer = StringBuffer('{');
    var first = true;
    for (final entry in json.entries) {
      if (!first) buffer.write(',');
      first = false;
      buffer.write('"${entry.key}":${_value(entry.value)}');
    }
    buffer.write('}');
    return buffer.toString();
  }

  static String _value(Object? value) {
    if (value == null) return 'null';
    if (value is num || value is bool) return '$value';
    if (value is Map<String, dynamic>) return _encode(value);
    if (value is List) return '[${value.map(_value).join(',')}]';
    return '"${value.toString().replaceAll('"', '\\"')}"';
  }
}

/// Thrown when a bill was queued offline. The UI treats this as SUCCESS
/// (print the LOCAL receipt now), not as an error.
class OfflineQueuedException implements Exception {
  OfflineQueuedException({
    required this.localBillNo,
    required this.idempotencyKey,
    this.cause,
  });

  final String localBillNo;
  final String idempotencyKey;
  final ApiException? cause;

  @override
  String toString() => 'OfflineQueuedException($localBillNo)';
}
