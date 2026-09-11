import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import 'data/models/wallet_models.dart';
import 'data/repositories/wallet_repository.dart';
import 'razorpay_checkout.dart';

/// State holder for the Customer Wallet (same ChangeNotifier style as
/// [MenuController] / [CartController] — no codegen, plain `notifyListeners`).
///
/// Owns the dashboard's balance + history and drives the Razorpay recharge
/// handshake:
///
///   1. [load] paints the dashboard from GET /api/wallet/balance.
///   2. [recharge] runs initiate → Razorpay overlay → verify and refreshes
///      the snapshot so the UI shows the credited balance immediately.
class WalletController with ChangeNotifier implements Listenable {
  WalletController({required WalletRepository repository})
      : _repository = repository;

  final WalletRepository _repository;

  WalletSnapshot? snapshot;
  bool loading = false;
  String? error;

  /// True while the Razorpay sheet is open / verification is in flight —
  /// the recharge button shows a spinner and double-taps are ignored.
  bool recharging = false;

  WalletTransactionModel? get lastTransaction {
    final transactions = snapshot?.transactions;
    if (transactions == null || transactions.isEmpty) return null;
    return transactions.first;
  }

  double get balance => snapshot?.balanceValue ?? 0;

  /// 1% wallet deduction preview for a bill of [billTotal] — capped at the
  /// available balance exactly like the server-side WalletService does, so
  /// the POS bill sheet never promises points the wallet cannot cover.
  double deductionPreviewFor(double billTotal) {
    final raw = ((billTotal * 100).roundToDouble() / 100) * 0.01;
    final rounded = (raw * 100).roundToDouble() / 100;
    return rounded.clamp(0, balance).toDouble();
  }

  /// Fetch balance + history (dashboard open / pull-to-refresh).
  Future<void> load() async {
    if (loading) return;
    loading = true;
    error = null;
    notifyListeners();

    try {
      snapshot = await _repository.balance();
      error = null;
    } catch (rawError) {
      // Catch ALL failures (not just ApiException) — a raw escape would skip
      // `loading = false` and leave the wallet dashboard spinning forever.
      error = apiExceptionFrom(rawError).message;
    }

    loading = false;
    notifyListeners();
  }

  /// Full recharge handshake. [openCheckout] presents the Razorpay overlay
  /// for the initiated order and resolves with the payment callback payload;
  /// returns the verify response, or null when the user cancelled / payment
  /// failed (in which case [error] carries a user-facing message).
  Future<RechargeResult?> recharge({
    required double amount,
    required Future<RechargePayment> Function(RechargeOrder order)
        openCheckout,
  }) async {
    if (recharging) return null;
    recharging = true;
    error = null;
    notifyListeners();

    try {
      // 1. Server creates the Razorpay order.
      final order = await _repository.initiateRecharge(amount: amount);

      // 2. Razorpay checkout overlay (payment_id + signature on success).
      final payment = await openCheckout(order);

      // 3. Server-side signature verification + wallet credit.
      final result = await _repository.verifyRecharge(
        paymentId: payment.paymentId,
        orderId: payment.orderId,
        signature: payment.signature,
      );

      // 4. Refresh so the dashboard shows the credited balance + ledger row.
      await load();
      return result;
    } on RazorpayCancelledException {
      error = 'Recharge cancelled.';
    } on RazorpayErrorException catch (exception) {
      error = exception.message;
    } on ApiException catch (exception) {
      error = exception.message;
    } finally {
      recharging = false;
      notifyListeners();
    }
    return null;
  }

  /// Quick refresh used after a bill settles (the 1% debit happened
  /// server-side — keep the dashboard honest).
  Future<void> refresh() => load();
}
