import 'package:flutter/material.dart';

import '../../features/orders/cart_controller.dart';
import '../../features/orders/data/models/order_models.dart';
import '../widgets/amount.dart';
import '../widgets/status_banner.dart';

/// The open bill — line items, payment/order-type pickers, customer fields
/// and the settle button.
///
/// Reactive: rendered inside `ListenableBuilder(listenable: cartController)`,
/// so every quantity bump, payment switch and discount edit re-renders this
/// pane immediately.
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
  });

  final CartController cart;
  final String error;
  final bool settling;
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
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    final hasItems = !cart.isEmpty;

    return Container(
      margin: EdgeInsets.all(8),
      padding: EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: scheme.surfaceContainer,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            spacing: 8,
            children: [
              Icon(Icons.shopping_bag, size: 20, color: scheme.primary),
              Expanded(
                child: Text(
                  hasItems
                      ? 'Bill — ${cart.totalQuantity} item(s)'
                      : 'Add items to start the bill',
                  style: theme.textTheme.titleSmall,
                ),
              ),
              if (hasItems)
                TextButton(
                  onPressed: () => cart.clear(),
                  child: Text('Clear',
                      style: theme.textTheme.labelMedium, selectionColor: scheme.error),
                ),
            ],
          ),
          SizedBox(height: 8),
          StatusBanner(text: error, error: true),
          SizedBox(height: 4),
          if (hasItems) _lines(theme, scheme),
          if (hasItems) _orderTypeRow(theme, scheme),
          if (hasItems) _paymentRow(theme, scheme),
          if (hasItems) _totals(theme, scheme),
          _settleBar(theme, scheme, hasItems),
        ],
      ),
    );
  }

  Widget _lines(ThemeData theme, ColorScheme scheme) {
    return SizedBox(
      height: 180,
      child: ListView(
        children: cart.lines.map((line) {
          return Row(
            spacing: 8,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(line.foodItem.name, style: theme.textTheme.bodyMedium),
                    Text('${inr(line.foodItem.price)} each',
                        style: theme.textTheme.bodySmall,
                        selectionColor: scheme.onSurfaceVariant),
                  ],
                ),
              ),
              IconButton(
                icon: Icon(Icons.remove_circle_outline, size: 18),
                tooltip: 'Remove',
                onPressed: () => cart.setQuantity(line.foodItem.id, line.quantity - 1),
              ),
              Text('${line.quantity}',
                  style: theme.textTheme.labelLarge,
                  selectionColor: scheme.onSurface),
              IconButton(
                icon: Icon(Icons.add_circle, size: 18),
                tooltip: 'Add one',
                onPressed: () => cart.setQuantity(line.foodItem.id, line.quantity + 1),
              ),
              SizedBox(
                width: 76,
                child: Text(inr(line.lineTotal),
                    textAlign: TextAlign.end,
                    style: theme.textTheme.bodyMedium,
                    selectionColor: scheme.onSurface),
              ),
            ],
          );
        }).toList(),
      ),
    );
  }

  Widget _optionRow({
    required ThemeData theme,
    required List<({String label, bool selected, VoidCallback onTap})> options,
  }) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: options.map((option) {
        final label = Text(option.label, style: theme.textTheme.labelMedium);
        return option.selected
            ? FilledButton(onPressed: option.onTap, child: label)
            : OutlinedButton(onPressed: option.onTap, child: label);
      }).toList(),
    );
  }

  Widget _orderTypeRow(ThemeData theme, ColorScheme scheme) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 92,
          child: Text('Order type', style: theme.textTheme.labelMedium),
        ),
        Expanded(
          child: _optionRow(
            theme: theme,
            options: OrderType.values.map((type) {
              return (
                label: type.label,
                selected: cart.orderType == type,
                onTap: () => cart.setOrderType(type),
              );
            }).toList(),
          ),
        ),
      ],
    );
  }
  Widget _paymentRow(ThemeData theme, ColorScheme scheme) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 92,
          child: Text('Payment', style: theme.textTheme.labelMedium),
        ),
        Expanded(
          child: _optionRow(
            theme: theme,
            options: PaymentMode.values.map((mode) {
              return (
                label: mode.label,
                selected: cart.paymentMode == mode,
                onTap: () => cart.setPaymentMode(mode),
              );
            }).toList(),
          ),
        ),
      ],
    );
  }

  Widget _totals(ThemeData theme, ColorScheme scheme) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TextField(
          controller: discountController,
          decoration: InputDecoration(
            labelText: 'Discount (₹)',
            icon: Icon(Icons.currency_rupee),
          ),
          onChanged: onDiscountChanged,
        ),
        SizedBox(height: 4),
        TextField(
          controller: customerController,
          decoration: InputDecoration(labelText: 'Customer name'),
          onChanged: onCustomerChanged,
        ),
        SizedBox(height: 4),
        TextField(
          controller: phoneController,
          decoration: InputDecoration(
            labelText: 'Customer phone',
            icon: Icon(Icons.phone),
          ),
          onChanged: onPhoneChanged,
        ),
        SizedBox(height: 4),
        if (cart.paymentMode == PaymentMode.upi)
          TextField(
            controller: upiRefController,
            decoration: InputDecoration(
              labelText: 'UPI transaction ref',
              icon: Icon(Icons.qr_code),
            ),
            onChanged: onUpiRefChanged,
          ),
        SizedBox(height: 8),
        Row(
          children: [
            Expanded(
              child: Text('Subtotal', style: theme.textTheme.bodyMedium),
            ),
            Text(inr(cart.subtotal), style: theme.textTheme.bodyMedium),
          ],
        ),
        if (cart.effectiveDiscount > 0)
          Row(
            children: [
              Expanded(
                child: Text('Discount', style: theme.textTheme.bodyMedium),
              ),
              Text('-${inr(cart.effectiveDiscount)}',
                  style: theme.textTheme.bodyMedium),
            ],
          ),
        Row(
          children: [
            Expanded(
              child: Text('PAYABLE', style: theme.textTheme.titleSmall),
            ),
            Text(inr(cart.payable),
                style: theme.textTheme.titleMedium,
                selectionColor: scheme.primary),
          ],
        ),
      ],
    );
  }

  Widget _settleBar(ThemeData theme, ColorScheme scheme, bool hasItems) {
    return Row(
      children: [
        Expanded(
          child: FilledButton.icon(
            icon: Icon(Icons.check_circle, size: 20),
            label: Text(
              settling
                  ? 'Settling…'
                  : (hasItems ? 'Settle ${inr(cart.payable)}' : 'Settle'),
              style: theme.textTheme.titleSmall,
            ),
            onPressed: settling || !hasItems ? () {} : () => onSettle?.call(),
          ),
        ),
      ],
    );
  }
}