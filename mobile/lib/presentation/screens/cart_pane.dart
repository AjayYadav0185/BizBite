import 'package:flutter/material.dart';

import '../../features/orders/cart_controller.dart';
import '../../features/orders/data/models/cart_line.dart';
import '../../features/orders/data/models/order_models.dart';
import '../../features/wallet/wallet_controller.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/amount.dart';
import '../widgets/status_banner.dart';

/// Screen 2 — Cart & Billing Summary (the "bill" half of the POS).
///
/// Used two ways by [PosScreen]:
///  * side-sheet panel on tablets / landscape (≥ 840dp),
///  * inside a draggable bottom sheet on phones (opened from the bill bar).
///
/// Structure: fixed header (item count + clear) → scrollable line list with
/// quantity steppers and per-line kitchen notes → payment chips + optional
/// customer/discount details → FIXED bottom billing summary with the
/// high-contrast "Process & Print Receipt" action.
///
/// Reactive: rendered inside `ListenableBuilder(listenable: cartController)`,
/// so every quantity bump, payment switch and discount edit re-renders
/// immediately.
class CartPane extends StatelessWidget {
  const CartPane({
    super.key,
    required this.cart,
    required this.error,
    required this.settling,
    required this.discountController,
    required this.customerController,
    required this.phoneController,
    required this.upiRefController,
    required this.onDiscountChanged,
    required this.onCustomerChanged,
    required this.onPhoneChanged,
    required this.onUpiRefChanged,
    required this.onSettle,
    this.wallet,
  });

  final CartController cart;
  final String error;
  final bool settling;

  /// Customer Wallet — when provided, the bill summary highlights the 1%
  /// points deduction ("Wallet Discount Applied (1%)") exactly as the
  /// server will debit it on settlement.
  final WalletController? wallet;
  final TextEditingController discountController;
  final TextEditingController customerController;
  final TextEditingController phoneController;
  final TextEditingController upiRefController;
  final void Function(String value)? onDiscountChanged;
  final void Function(String value)? onCustomerChanged;
  final void Function(String value)? onPhoneChanged;
  final void Function(String value)? onUpiRefChanged;
  final VoidCallback? onSettle;

  @override
  Widget build(BuildContext context) {
    final hasItems = !cart.isEmpty;

    return Column(
      children: [
        _header(context, hasItems),
        StatusBanner(text: error, error: true),
        Expanded(child: hasItems ? _billBody(context) : _emptyState(context)),
        _billingFooter(context, hasItems),
      ],
    );
  }

  // --- Header ---------------------------------------------------------------

  Widget _header(BuildContext context, bool hasItems) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 14, 8, 8),
      child: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
              color: BizBiteTheme.brandSoft,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(Icons.receipt_long_rounded,
                size: 19, color: BizBiteTheme.brandDeep),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Current Bill',
                    style: theme.textTheme.titleMedium
                        ?.copyWith(fontWeight: FontWeight.w800)),
                Text(
                  hasItems
                      ? '${cart.totalQuantity} item(s) · ${cart.lines.length} line(s)'
                      : 'Nothing added yet',
                  style: theme.textTheme.bodySmall
                      ?.copyWith(color: scheme.onSurfaceVariant),
                ),
              ],
            ),
          ),
          if (hasItems)
            TextButton(
              onPressed: () => cart.clear(),
              style: TextButton.styleFrom(
                foregroundColor: scheme.error,
                textStyle:
                    const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
              ),
              child: const Text('Clear'),
            ),
        ],
      ),
    );
  }

  // --- Body -----------------------------------------------------------------

  Widget _billBody(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
      children: [
        for (final line in cart.lines) _lineTile(context, line),
        const SizedBox(height: 8),
        _paymentSection(context),
        _customerSection(context),
        const SizedBox(height: 4),
        _totalsRows(context),
      ],
    );
  }

  Widget _emptyState(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.room_service_rounded, size: 44, color: scheme.outline),
          const SizedBox(height: 10),
          Text('Add items to start the bill',
              style: theme.textTheme.bodyLarge
                  ?.copyWith(color: scheme.onSurfaceVariant)),
          const SizedBox(height: 4),
          Text('Tap any item on the menu grid',
              style: theme.textTheme.bodySmall
                  ?.copyWith(color: scheme.outline)),
        ],
      ),
    );
  }

  /// One bill line: name + unit price, quantity stepper, line total and an
  /// "Add note" affordance for kitchen instructions.
  Widget _lineTile(BuildContext context, CartLine line) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      line.foodItem.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontSize: 15, fontWeight: FontWeight.w700),
                    ),
                    Text(
                      '${inr(line.foodItem.price)} each',
                      style: theme.textTheme.bodySmall
                          ?.copyWith(color: scheme.onSurfaceVariant),
                    ),
                  ],
                ),
              ),
              _stepper(context, line),
              const SizedBox(width: 10),
              SizedBox(
                width: 76,
                child: Text(
                  inr(line.lineTotal),
                  textAlign: TextAlign.end,
                  style: const TextStyle(
                          fontSize: 14.5, fontWeight: FontWeight.w800)
                      .merge(BizBiteTheme.numeral),
                ),
              ),
            ],
          ),
          _noteRow(context, line),
        ],
      ),
    );
  }

  /// 32dp quantity stepper — "−" removes, "+" adds; hitting 0 drops the line
  /// (CartController semantics).
  Widget _stepper(BuildContext context, CartLine line) {
    return Container(
      height: 32,
      decoration: ShapeDecoration(
        shape: StadiumBorder(
            side: const BorderSide(color: BizBiteTheme.hairline)),
        color: Colors.white,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          InkWell(
            borderRadius:
                const BorderRadius.horizontal(left: Radius.circular(16)),
            onTap: () =>
                cart.setQuantity(line.foodItem.id, line.quantity - 1),
            child: const SizedBox(
              width: 32,
              height: 32,
              child: Icon(Icons.remove_rounded,
                  size: 16, color: BizBiteTheme.inkMuted),
            ),
          ),
          SizedBox(
            width: 26,
            child: Text(
              '${line.quantity}',
              textAlign: TextAlign.center,
              style: const TextStyle(
                      fontSize: 13.5, fontWeight: FontWeight.w800)
                  .merge(BizBiteTheme.numeral),
            ),
          ),
          InkWell(
            borderRadius:
                const BorderRadius.horizontal(right: Radius.circular(16)),
            onTap: () =>
                cart.setQuantity(line.foodItem.id, line.quantity + 1),
            child: const SizedBox(
              width: 32,
              height: 32,
              child: Icon(Icons.add_rounded,
                  size: 16, color: BizBiteTheme.brandDeep),
            ),
          ),
        ],
      ),
    );
  }

  Widget _noteRow(BuildContext context, CartLine line) {
    final hasNote = line.note.isNotEmpty;
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return InkWell(
      onTap: () => _editNote(context, line),
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.only(top: 6),
        child: Row(
          children: [
            Icon(
              Icons.edit_note_rounded,
              size: 17,
              color: hasNote ? BizBiteTheme.brand : scheme.outline,
            ),
            const SizedBox(width: 5),
            Expanded(
              child: Text(
                hasNote ? line.note : 'Add note',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: hasNote ? FontWeight.w600 : FontWeight.w500,
                  color: hasNote ? BizBiteTheme.brandDeep : scheme.outline,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _editNote(BuildContext context, CartLine line) async {
    final controller = TextEditingController(text: line.note);
    final note = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Note — ${line.foodItem.name}',
            style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w700)),
        content: TextField(
          controller: controller,
          autofocus: true,
          maxLines: 2,
          decoration:
              const InputDecoration(hintText: 'e.g. No onion, extra spicy'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, controller.text),
            child: const Text('Save'),
          ),
        ],
      ),
    );
    controller.dispose();
    if (note != null) cart.setNote(line.foodItem.id, note);
  }

  /// Payment mode chips + the UPI reference field when UPI is selected.
  Widget _paymentSection(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'PAYMENT',
          style: theme.textTheme.labelMedium?.copyWith(
            fontWeight: FontWeight.w800,
            letterSpacing: 0.8,
            color: scheme.onSurfaceVariant,
          ),
        ),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            for (final mode in PaymentMode.values)
              ChoiceChip(
                label: Text(mode.label),
                selected: cart.paymentMode == mode,
                onSelected: (_) => cart.setPaymentMode(mode),
                showCheckmark: false,
                labelStyle: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: cart.paymentMode == mode
                      ? BizBiteTheme.brandDeep
                      : scheme.onSurfaceVariant,
                ),
                selectedColor: BizBiteTheme.brandSoft,
                backgroundColor: Colors.white,
                side: const BorderSide(color: BizBiteTheme.hairline),
              ),
          ],
        ),
        if (cart.paymentMode == PaymentMode.upi) ...[
          const SizedBox(height: 10),
          TextField(
            controller: upiRefController,
            onChanged: onUpiRefChanged,
            decoration: const InputDecoration(
              labelText: 'UPI transaction ref',
              prefixIcon: Icon(Icons.qr_code_rounded),
            ),
          ),
        ],
        const SizedBox(height: 6),
      ],
    );
  }

  /// Optional customer & discount fields, collapsed by default to keep the
  /// bill scannable during rush hours.
  Widget _customerSection(BuildContext context) {
    return Theme(
      data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
      child: ExpansionTile(
        tilePadding: EdgeInsets.zero,
        childrenPadding: const EdgeInsets.only(bottom: 10),
        title: const Text(
          'Customer & discount (optional)',
          style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600),
        ),
        children: [
          TextField(
            controller: discountController,
            onChanged: onDiscountChanged,
            keyboardType:
                const TextInputType.numberWithOptions(decimal: true),
            decoration: const InputDecoration(
              labelText: 'Discount (₹)',
              prefixIcon: Icon(Icons.currency_rupee_rounded),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: customerController,
            onChanged: onCustomerChanged,
            textCapitalization: TextCapitalization.words,
            decoration: const InputDecoration(
              labelText: 'Customer name',
              prefixIcon: Icon(Icons.person_outline_rounded),
            ),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: phoneController,
            onChanged: onPhoneChanged,
            keyboardType: TextInputType.phone,
            decoration: const InputDecoration(
              labelText: 'Customer phone',
              prefixIcon: Icon(Icons.phone_outlined),
            ),
          ),
        ],
      ),
    );
  }

  /// Subtotal / discount / (tax, once the backend exposes it) rows above the
  /// fixed grand-total footer.
  Widget _totalsRows(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    Widget row(String label, String value, {Color? color}) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          children: [
            Expanded(
              child: Text(label,
                  style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: color ?? scheme.onSurfaceVariant)),
            ),
            Text(
              value,
              style: TextStyle(
                      fontSize: 14.5,
                      fontWeight: FontWeight.w800,
                      color: color ?? scheme.onSurface)
                  .merge(BizBiteTheme.numeral),
            ),
          ],
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        row('Subtotal', inr(cart.subtotal)),
        if (cart.effectiveDiscount > 0)
          row('Discount', '-${inr(cart.effectiveDiscount)}',
              color: BizBiteTheme.success),
        // Customer Wallet — highlight the 1% points deduction exactly as
        // the server will debit it (capped at the available balance).
        if (wallet != null && cart.payable > 0)
          _walletRow(context, cart.payable),
        // NOTE: Tax rows render here automatically once the backend returns
        // tax breakdown fields on POST /api/orders (hooks reserved in
        // POS_UI_DESIGN_SPEC.md).
      ],
    );
  }

  /// "Wallet Discount Applied (1%)" — points that will be debited for this
  /// bill, plus the balance remaining afterwards. Rendered only when a
  /// wallet is wired in (POS billing).
  Widget _walletRow(BuildContext context, double payable) {
    final deduction = wallet!.deductionPreviewFor(payable);
    if (deduction <= 0) return const SizedBox.shrink();

    final after = wallet!.balance - deduction;

    return Container(
      margin: const EdgeInsets.only(top: 6, bottom: 2),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: AppColors.infoBg,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: BizBiteTheme.brand.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          const Icon(Icons.account_balance_wallet_rounded,
              size: 15, color: BizBiteTheme.brand),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Wallet Discount Applied (1%)',
                  style: const TextStyle(
                    fontSize: 11.5,
                    fontWeight: FontWeight.w800,
                    color: BizBiteTheme.brandDeep,
                  ),
                ),
                Text(
                  'Deducts ${deduction.toStringAsFixed(2)} pts · balance after ${after.toStringAsFixed(2)}',
                  style: const TextStyle(
                    fontSize: 10,
                    color: AppColors.muted,
                  ).merge(BizBiteTheme.numeral),
                ),
              ],
            ),
          ),
          Text(
            '-${deduction.toStringAsFixed(2)} pts',
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w800,
              color: BizBiteTheme.brandDeep,
            ).merge(BizBiteTheme.numeral),
          ),
        ],
      ),
    );
  }

  /// Fixed bottom billing summary — grand total in large bold numerals plus
  /// the high-contrast "Process & Print Receipt" action (56dp tall).
  Widget _billingFooter(BuildContext context, bool hasItems) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: BizBiteTheme.hairline)),
      ),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'GRAND TOTAL',
                      style: theme.textTheme.labelSmall?.copyWith(
                        fontWeight: FontWeight.w800,
                        letterSpacing: 1.1,
                        color: scheme.onSurfaceVariant,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      inr(cart.payable),
                      style: const TextStyle(
                              fontSize: 25,
                              fontWeight: FontWeight.w800,
                              height: 1.05)
                          .merge(BizBiteTheme.numeral),
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.verified_rounded,
                size: 20,
                color: hasItems ? BizBiteTheme.success : scheme.outline,
              ),
            ],
          ),
          const SizedBox(height: 10),
          SizedBox(
            width: double.infinity,
            height: 56,
            child: FilledButton.icon(
              onPressed: settling || !hasItems ? null : () => onSettle?.call(),
              style: FilledButton.styleFrom(
                backgroundColor: BizBiteTheme.brand,
                disabledBackgroundColor: BizBiteTheme.hairline,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16)),
              ),
              icon: settling
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                          strokeWidth: 2.2, color: Colors.white),
                    )
                  : const Icon(Icons.print_rounded, size: 20),
              label: Text(
                settling ? 'Processing…' : 'Process & Print Receipt',
                style: const TextStyle(
                    fontSize: 15.5, fontWeight: FontWeight.w800),
              ),
            ),
          ),
        ],
      ),
    );
  }
}