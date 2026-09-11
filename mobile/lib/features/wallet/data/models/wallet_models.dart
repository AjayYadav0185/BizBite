import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';

/// One movement of wallet points — mirrors a row of
/// `tbl_pos_wallet_transactions` as serialized by `GET /api/wallet/balance`.
class WalletTransactionModel extends Equatable {
  const WalletTransactionModel({
    required this.id,
    required this.amount,
    required this.type,
    required this.description,
    required this.referenceId,
    required this.balanceAfter,
    required this.createdAt,
  });

  final int id;

  /// Signed 2dp string — negative for debits, positive for credits.
  final String amount;

  /// 'credit' or 'debit'.
  final String type;
  final String description;
  final String? referenceId;
  final String? balanceAfter;
  final DateTime? createdAt;

  bool get isCredit => type == 'credit';
  bool get isDebit => type == 'debit';

  double get signedValue => toDouble(amount);

  factory WalletTransactionModel.fromJson(Map<String, dynamic> json) {
    return WalletTransactionModel(
      id: toInt(json['id']),
      amount: toNullableString(json['amount'], fallback: '0.00'),
      type: toNullableString(json['type'], fallback: 'credit'),
      description: toNullableString(json['description']),
      referenceId: (json['reference_id'] == null)
          ? null
          : toNullableString(json['reference_id']),
      balanceAfter: (json['balance_after'] == null)
          ? null
          : toNullableString(json['balance_after']),
      createdAt: DateTime.tryParse(
        toNullableString(json['created_at']),
      )?.toLocal(),
    );
  }

  @override
  List<Object?> get props => [
        id, amount, type, description, referenceId, balanceAfter, createdAt,
      ];
}

/// Snapshot returned by `GET /api/wallet/balance`.
class WalletSnapshot extends Equatable {
  const WalletSnapshot({
    required this.balance,
    required this.transactions,
  });

  /// Current points, 2dp string (1 point = ₹1).
  final String balance;
  final List<WalletTransactionModel> transactions;

  double get balanceValue => toDouble(balance);

  factory WalletSnapshot.fromJson(Map<String, dynamic> json) {
    return WalletSnapshot(
      balance: toNullableString(json['wallet_balance'], fallback: '0.00'),
      transactions: toListOfMaps(json['transactions'])
          .map(WalletTransactionModel.fromJson)
          .toList(growable: false),
    );
  }

  @override
  List<Object?> get props => [balance, transactions];
}

/// Response of `POST /api/wallet/recharge/initiate` — everything the
/// Razorpay checkout overlay needs to open.
class RechargeOrder extends Equatable {
  const RechargeOrder({
    required this.razorpayOrderId,
    required this.amount,
    required this.currency,
    this.keyId,
  });

  final String razorpayOrderId;
  final String amount;
  final String currency;
  final String? keyId;

  factory RechargeOrder.fromJson(Map<String, dynamic> json) {
    return RechargeOrder(
      razorpayOrderId:
          toNullableString(json['razorpay_order_id']),
      amount: toNullableString(json['amount'], fallback: '0.00'),
      currency: toNullableString(json['currency'], fallback: 'INR'),
      keyId: (json['key_id'] == null) ? null : toNullableString(json['key_id']),
    );
  }

  @override
  List<Object?> get props =>
      [razorpayOrderId, amount, currency, keyId];
}

/// Response of `POST /api/wallet/recharge/verify` after a successful payment.
class RechargeResult extends Equatable {
  const RechargeResult({
    required this.message,
    required this.creditedPoints,
    required this.walletBalance,
  });

  final String message;
  final String creditedPoints;
  final String walletBalance;

  factory RechargeResult.fromJson(Map<String, dynamic> json) {
    return RechargeResult(
      message: toNullableString(json['message']),
      creditedPoints: toNullableString(
        json['credited_points'],
        fallback: '0.00',
      ),
      walletBalance: toNullableString(
        json['wallet_balance'],
        fallback: '0.00',
      ),
    );
  }

  @override
  List<Object?> get props => [message, creditedPoints, walletBalance];
}
