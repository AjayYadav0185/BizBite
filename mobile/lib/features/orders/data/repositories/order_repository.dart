import 'package:dio/dio.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/utils/parse_utils.dart';
import '../config/api_config.dart';
import '../features/orders/data/models/order_models.dart';
import '../features/orders/data/models/order_receipt_model.dart';

/// Thin typed wrapper over `POST /api/orders`.
///
/// Business rules (price snapshotting, GST math, per-day bill numbers,
/// idempotency) all live server-side in the shared `OrderService`; this class
/// only serializes the cart and parses the returned receipt.
class OrderRepository {
  OrderRepository({required DioClient client}) : _client = client;

  final DioClient _client;

  /// Place an order. Returns the server-computed receipt (201 Created).
  Future<OrderReceiptModel> place(PlaceOrderRequest request) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.orders,
        data: request.toJson(),
      );
      return OrderReceiptModel.fromJson(toMap(response.data));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }
}