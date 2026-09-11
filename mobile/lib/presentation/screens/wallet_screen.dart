import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../features/wallet/data/models/wallet_models.dart';
import '../../features/wallet/razorpay_checkout.dart';
import '../../features/wallet/wallet_controller.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/amount.dart';

/// Wallet Dashboard — current points, transaction ledger (credits in green,
/// debits in red) and the Razorpay recharge entry point.
///
/// The recharge handshake is owned by [WalletController.recharge]:
/// initiate → Razorpay overlay ([RazorpayCheckout.open]) → verify → refresh.
class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key, required this.wallet});

  final WalletController wallet;

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  final TextEditingController _amount = TextEditingController();
  final RazorpayCheckout _checkout = RazorpayCheckout();

  static const List<double> _quickAmounts = [100, 250, 500, 1000];

  @override
  void initState() {
    super.initState();
    widget.wallet.load();
  }

  @override
  void dispose() {
    _amount.dispose();
    super.dispose();
  }

  Future<void> _recharge() async {
    final amount = double.tryParse(_amount.text.trim());
    if (amount == null || amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('Enter a valid recharge amount (e.g. 100).'),
        backgroundColor: AppColors.error,
      ));
      return;
    }

    final result = await widget.wallet.recharge(
      amount: amount,
      openCheckout: _checkout.open,
    );

    if (!mounted) return;

    if (result != null) {
      _amount.clear();
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text('${result.message} +${result.creditedPoints} points.'),
        backgroundColor: BizBiteTheme.success,
      ));
    } else if ((widget.wallet.error ?? '').isNotEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(widget.wallet.error!),
        backgroundColor: AppColors.error,
      ));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Wallet'),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
      ),
      body: ListenableBuilder(
        listenable: widget.wallet,
        builder: (context, _) {
          final wallet = widget.wallet;

          if (wallet.loading && wallet.snapshot == null) {
            return const Center(child: CircularProgressIndicator());
          }

          if (wallet.snapshot == null) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(wallet.error ?? 'Wallet unavailable.'),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: () => wallet.load(),
                    child: const Text('Retry'),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () => wallet.load(),
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                _balanceCard(context, wallet),
                const SizedBox(height: 16),
                _rechargeCard(context, wallet),
                const SizedBox(height: 20),
                const _LedgerHeading(),
                if (wallet.snapshot!.transactions.isEmpty)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 32),
                    child: Center(
                      child: Text(
                        'No transactions yet.\nPoints appear here after your first bill or recharge.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: AppColors.muted),
                      ),
                    ),
                  )
                else
                  ...wallet.snapshot!.transactions.map(
                    (tx) => _TransactionTile(tx: tx),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _balanceCard(BuildContext context, WalletController wallet) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: AppGradients.brandMain,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'WALLET POINTS',
                  style: TextStyle(
                    fontSize: 10.5,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 1.2,
                    color: Colors.white70,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  inr(wallet.balance),
                  style: const TextStyle(
                    fontSize: 30,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ).merge(BizBiteTheme.numeral),
                ),
                const SizedBox(height: 4),
                const Text(
                  '1 point = ₹1 · 1% of every bill is auto-debited',
                  style: TextStyle(fontSize: 11.5, color: Colors.white70),
                ),
              ],
            ),
          ),
          Container(
            width: 46,
            height: 46,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.18),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.account_balance_wallet_rounded,
              color: Colors.white,
              size: 22,
            ),
          ),
        ],
      ),
    );
  }

  Widget _rechargeCard(BuildContext context, WalletController wallet) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: BizBiteTheme.hairline),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.add_card_rounded,
                  size: 18, color: BizBiteTheme.brand),
              const SizedBox(width: 8),
              const Text(
                'Recharge Wallet',
                style: TextStyle(
                  fontSize: 14.5,
                  fontWeight: FontWeight.w800,
                  color: AppColors.ink,
                ),
              ),
              const Spacer(),
              Text(
                '₹100 = 100 points',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: AppColors.muted,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              for (final quick in _quickAmounts) ...[
                Expanded(
                  child: OutlinedButton(
                    onPressed: wallet.recharging
                        ? null
                        : () => setState(() => _amount.text =
                              quick.toStringAsFixed(0)),
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: BizBiteTheme.hairline),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                    child: Text(
                      '+${quick.toStringAsFixed(0)}',
                      style: const TextStyle(
                        fontSize: 12.5,
                        fontWeight: FontWeight.w800,
                        color: BizBiteTheme.brand,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
              ],
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _amount,
                  keyboardType:
                      const TextInputType.numberWithOptions(decimal: true),
                  enabled: !wallet.recharging,
                  decoration: InputDecoration(
                    prefixText: '₹ ',
                    labelText: 'Amount',
                    filled: true,
                    fillColor: AppColors.surfaceMuted,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              SizedBox(
                height: 48,
                child: FilledButton.icon(
                  onPressed: wallet.recharging ? null : _recharge,
                  style: FilledButton.styleFrom(
                    backgroundColor: BizBiteTheme.brand,
                    disabledBackgroundColor: BizBiteTheme.hairline,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  icon: wallet.recharging
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.bolt_rounded, size: 18),
                  label: const Text(
                    'Recharge',
                    style: TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

/// Ledger section header.
class _LedgerHeading extends StatelessWidget {
  const _LedgerHeading();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.only(left: 4, bottom: 8),
      child: Align(
        alignment: Alignment.centerLeft,
        child: Text(
          'TRANSACTION HISTORY',
          style: TextStyle(
            fontSize: 10.5,
            fontWeight: FontWeight.w800,
            letterSpacing: 1.2,
            color: AppColors.muted,
          ),
        ),
      ),
    );
  }
}

/// One ledger row — green for credits, red for debits.
class _TransactionTile extends StatelessWidget {
  const _TransactionTile({required this.tx});

  final WalletTransactionModel tx;

  static final DateFormat _stamp = DateFormat('dd MMM, hh:mm a');

  @override
  Widget build(BuildContext context) {
    final color = tx.isCredit ? BizBiteTheme.success : AppColors.error;
    final sign = tx.isCredit ? '+' : '-';

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: BizBiteTheme.hairline),
      ),
      child: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: Icon(
              tx.isCredit
                  ? Icons.arrow_downward_rounded
                  : Icons.arrow_upward_rounded,
              size: 16,
              color: color,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  tx.description,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w800,
                    color: AppColors.ink,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  tx.createdAt == null
                      ? (tx.referenceId ?? '')
                      : '${_stamp.format(tx.createdAt!)}'
                          '${tx.referenceId == null ? '' : ' · ${tx.referenceId}'}',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppColors.muted,
                  ),
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '$sign${inr(tx.signedValue.abs())}',
                style: TextStyle(
                  fontSize: 14.5,
                  fontWeight: FontWeight.w800,
                  color: color,
                ).merge(BizBiteTheme.numeral),
              ),
              if (tx.balanceAfter != null)
                Text(
                  'bal ${inr(double.tryParse(tx.balanceAfter!) ?? 0)}',
                  style: const TextStyle(
                    fontSize: 10.5,
                    color: AppColors.muted,
                  ).merge(BizBiteTheme.numeral),
                ),
            ],
          ),
        ],
      ),
    );
  }
}
