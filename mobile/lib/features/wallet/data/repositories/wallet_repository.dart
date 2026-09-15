import 'package:dio/dio.dart';

import '../../../../core/config/api_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/sync/cache_keys.dart';
import '../../../../core/sync/offline_gateway.dart';
import '../../../../core/sync/offline_sources.dart';
import '../../../../core/utils/parse_utils.dart';
import '../models/wallet_models.dart';

/// Typed gateway for the Customer Wallet endpoints (routes/api.php):
///
///   GET  /wallet/balance          → points + recent ledger history (cached)
///   POST /wallet/recharge/initiate → Razorpay order id (ONLINE ONLY)
///   POST /wallet/recharge/verify   → signature check + credit (ONLINE ONLY)
///
/// The balance is cached so the dashboard still renders the last known points
/// and history in a dead zone. Recharging is money + an external payment
/// gateway, so it can never be queued: the cashier is told to reconnect.
class WalletRepository {
  WalletRepository({required this._client, OfflineGateway? gateway})
      : _gateway = gateway ?? OfflineGateway();

  final DioClient _client;
  final OfflineGateway _gateway;

  /// GET /api/wallet/balance — network-first with a SQLite fallback.
  Future<WalletSnapshot> balance() async {
    final read = await _gateway.readRaw(
      key: CacheKeys.wallet,
      source: OfflineSources.wallet,
      fetch: () async {
        try {
          final response = await _client.get<dynamic>(ApiConfig.walletBalance);
          return response.data;
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );
    return WalletSnapshot.fromJson(toMap(read.data));
  }

  /// POST /api/wallet/recharge/initiate — create a Razorpay order for
  /// `amount` rupees (credited 1:1 as points after verification).
  Future<RechargeOrder> initiateRecharge({required double amount}) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.walletRechargeInitiate,
        data: {'amount': amount},
      );
      return RechargeOrder.fromJson(toMap(response.data));
    } on DioException catch (error) {
      throw _rechargeFailure(apiExceptionFrom(error), error);
    }
  }

  /// POST /api/wallet/recharge/verify — send the Razorpay callback payload
  /// for HMAC signature verification; on success the server credits the
  /// wallet and returns the new balance.
  Future<RechargeResult> verifyRecharge({
    required String paymentId,
    required String orderId,
    required String signature,
  }) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.walletRechargeVerify,
        data: {
          'razorpay_payment_id': paymentId,
          'razorpay_order_id': orderId,
          'razorpay_signature': signature,
        },
      );
      return RechargeResult.fromJson(toMap(response.data));
    } on DioException catch (error) {
      throw _rechargeFailure(apiExceptionFrom(error), error);
    }
  }

  /// Recharges are never queued: an unverified payment can never become
  /// points, so an offline attempt gets an explicit, cashier-ready message.
  ApiException _rechargeFailure(ApiException exception, Object cause) {
    if (!exception.isNetworkError) return exception;
    return ApiException(
      message: 'Recharge needs a connection — reconnect and try again.',
      type: ApiExceptionType.network,
      original: cause,
    );
  }
}
