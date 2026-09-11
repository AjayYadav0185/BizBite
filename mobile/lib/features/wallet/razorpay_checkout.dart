import 'dart:async';

import 'package:razorpay_flutter/razorpay_flutter.dart';

import 'data/models/wallet_models.dart';

/// Successful Razorpay checkout callback payload, relayed to the backend
/// for server-side HMAC signature verification.
class RechargePayment {
  const RechargePayment({
    required this.paymentId,
    required this.orderId,
    required this.signature,
  });

  final String paymentId;
  final String orderId;
  final String signature;
}

/// User dismissed the Razorpay sheet — not an error, just no payment.
class RazorpayCancelledException implements Exception {
  RazorpayCancelledException(this.message);
  final String message;

  @override
  String toString() => 'RazorpayCancelledException($message)';
}

/// Razorpay reported a failure (network, declined, invalid config).
class RazorpayErrorException implements Exception {
  RazorpayErrorException(this.message);
  final String message;

  @override
  String toString() => 'RazorpayErrorException($message)';
}

/// Thin wrapper over the official `razorpay_flutter` plugin.
///
/// The checkout overlay MUST be driven with the server-created order id
/// (`order_xxx` from POST /wallet/recharge/initiate) so the signature the
/// callback produces binds payment → order → wallet credit, and the backend
/// can verify it with the key secret that never leaves the server.
class RazorpayCheckout {
  RazorpayCheckout({this.appName = 'BizBite Wallet'});

  final String appName;

  /// Opens the Razorpay checkout sheet for [order] and resolves with the
  /// payment payload on success.
  ///
  /// Throws [RazorpayCancelledException] when the sheet is dismissed and
  /// [RazorpayErrorException] on any gateway failure.
  Future<RechargePayment> open(RechargeOrder order) {
    final completer = Completer<RechargePayment>();

    final razorpay = Razorpay();

    void cleanup() {
      razorpay.clear();
    }

    razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, (PaymentSuccessResponse response) {
      cleanup();
      completer.complete(RechargePayment(
        paymentId: response.paymentId ?? '',
        orderId: response.orderId ?? order.razorpayOrderId,
        signature: response.signature ?? '',
      ));
    });

    razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, (PaymentFailureResponse response) {
      cleanup();
      completer.completeError(RazorpayErrorException(
        response.message ?? 'Payment failed. Please try again.',
      ));
    });

    razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, (ExternalWalletResponse response) {
      // External wallets resolve through the success path; nothing to do.
    });

    final options = {
      // API key id (rzp_live_xxx / rzp_test_xxx). Prefer the server-sent key
      // so rotating keys on the backend instantly applies to the app.
      'key': order.keyId ?? '',
      'amount': _paise(order.amount),
      'currency': order.currency,
      'name': appName,
      'description': 'Wallet recharge — ${order.amount} points',
      'order_id': order.razorpayOrderId,
      // One-shot: the sheet is closed/disposed after a single attempt.
      'retry': {'enabled': false},
    };

    razorpay.open(options);
    return completer.future;
  }

  /// Rupees string (2dp) → integer paise, exactly what Razorpay expects.
  static int _paise(String rupees) {
    final value = double.tryParse(rupees) ?? 0;
    return (value * 100).round();
  }
}
