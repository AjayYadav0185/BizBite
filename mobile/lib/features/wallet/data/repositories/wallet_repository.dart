import 'package:dio/dio.dart';

import '../../../../core/config/api_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/utils/parse_utils.dart';
import '../models/wallet_models.dart';

/// Typed gateway for the Customer Wallet endpoints (routes/api.php):
///
///   GET  /wallet/balance          → current points + recent ledger history
///   POST /wallet/recharge/initiate → Razorpay order id for the checkout sheet
///   POST /wallet/recharge/verify   → server-side signature check + credit
class WalletRepository {
  WalletRepository({required this._client});

  final DioClient _client;

  /// GET /api/wallet/balance — balance + newest-first transaction history.
  Future<WalletSnapshot> balance() async {
    try {
      final response = await _client.get<dynamic>(ApiConfig.walletBalance);
      return WalletSnapshot.fromJson(toMap(response.data));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
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
      throw apiExceptionFrom(error);
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
      throw apiExceptionFrom(error);
    }
  }
}
