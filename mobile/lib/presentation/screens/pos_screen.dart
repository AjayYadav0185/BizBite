import 'package:flutter/material.dart' hide MenuController;

import '../../core/network/api_exception.dart';
import '../../features/auth/session_controller.dart';
import '../../features/menu/menu_controller.dart';
import '../../features/orders/cart_controller.dart';
import '../../features/orders/data/models/order_models.dart';
import '../../features/orders/order_checkout.dart';
import '../../features/orders/data/models/order_receipt_model.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/amount.dart';
import 'cart_pane.dart';
import 'menu_pane.dart';

/// The Pay Desk billing screen — mirrors the Laravel `BillingDashboard`
/// Livewire component (search + category filter + cart with quantity steppers
/// + payment/order-type pickers + live totals) but backed by the Sanctum API.
class PosScreen extends StatefulWidget {
  const PosScreen({
    super.key,
    required this.session,
    required this.menu,
    required this.cart,
    required this.checkout,
    this.onSettled,
  });

  final SessionController session;
  final MenuController menu;
  final CartController cart;
  final OrderCheckout checkout;

  /// Fired with the server receipt after a successful settlement. The app
  /// router listens and swaps to the ReceiptScreen.
  final void Function(OrderReceiptModel receipt)? onSettled;

  @override
  State<PosScreen> createState() => _PosScreenState();
}

class _PosScreenState extends State<PosScreen> {
  /// Side-sheet cart panel kicks in on tablets / landscape.
  static const double _sidePanelBreakpoint = 840;

  final TextEditingController _search = TextEditingController();
  TextEditingController _discount = TextEditingController();
  TextEditingController _customer = TextEditingController();
  TextEditingController _phone = TextEditingController();
  TextEditingController _upiRef = TextEditingController();

  int? _activeCategoryId;
  String _error = '';
  bool _settling = false;
  bool _billSheetOpen = false;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      backgroundColor: scheme.surfaceContainerLow,
      body: LayoutBuilder(
        builder: (context, constraints) {
          // Wide: split-screen POS — menu grid + fixed billing side-sheet.
          if (constraints.maxWidth >= _sidePanelBreakpoint) {
            return Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(child: _menu()),
                Container(
                  width: 380,
                  margin: const EdgeInsets.all(12),
                  clipBehavior: Clip.antiAlias,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: BizBiteTheme.hairline),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.06),
                        blurRadius: 18,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: _cartPane(),
                ),
              ],
            );
          }
          // Narrow: full-width grid + persistent dark bill bar.
          return Column(
            children: [
              Expanded(child: _menu()),
              _billBar(context),
            ],
          );
        },
      ),
    );
  }

  Widget _menu() {
    // React to BOTH the menu controller and the cart controller — the item
    // cards' "marked" state (brand border + qty badge) is computed from cart
    // lines at build time, so the grid must rebuild on every cart change.
    // Previously it only listened to `menu`, so after the first add the
    // badges went stale (new items were billed but never marked).
    return ListenableBuilder(
      listenable: Listenable.merge([widget.menu, widget.cart]),
      builder: (context, _) {
        return MenuPane(
          menu: widget.menu,
          cart: widget.cart,
          searchController: _search,
          activeCategoryId: _activeCategoryId,
          onSearchChanged: (value) {
            setState(() {});
          },
          onCategoryTap: (categoryId) {
            setState(() {
              _activeCategoryId = categoryId;
            });
          },
        );
      },
    );
  }

  Widget _cartPane() {
    return ListenableBuilder(
      listenable: widget.cart,
      builder: (context, _) {
        return CartPane(
          cart: widget.cart,
          error: _error,
          settling: _settling,
          discountController: _discount,
          customerController: _customer,
          phoneController: _phone,
          upiRefController: _upiRef,
          onDiscountChanged: (value) {
            widget.cart
                .setDiscountAmount(double.tryParse(value.trim()) ?? 0);
          },
          onCustomerChanged: widget.cart.setCustomerName,
          onPhoneChanged: widget.cart.setCustomerPhone,
          onUpiRefChanged: widget.cart.setUpiRef,
          onSettle: _settle,
        );
      },
    );
  }

  /// Narrow layout — persistent dark bill bar: live item count, order type,
  /// grand total and the primary "Process & Print" action. Tapping the
  /// summary opens the full bill as a bottom sheet.
  Widget _billBar(BuildContext context) {
    return ListenableBuilder(
      listenable: widget.cart,
      builder: (context, _) {
        final cart = widget.cart;
        final hasItems = !cart.isEmpty;

        return Container(
          margin: const EdgeInsets.fromLTRB(12, 4, 12, 12),
          padding: const EdgeInsets.fromLTRB(16, 10, 10, 10),
          decoration: BoxDecoration(
            color: BizBiteTheme.inkDark,
            borderRadius: BorderRadius.circular(18),
          ),
          child: Row(
            children: [
              Expanded(
                child: InkWell(
                  onTap: hasItems ? _openBillSheet : null,
                  borderRadius: BorderRadius.circular(12),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          hasItems
                              ? '${cart.totalQuantity} item(s) on bill'
                              : 'Bill is empty — tap items to add',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: hasItems ? Colors.white : Colors.white54,
                          ),
                        ),
                        const SizedBox(height: 1),
                        Text(
                          inr(cart.payable),
                          style: TextStyle(
                                  fontSize: 19,
                                  fontWeight: FontWeight.w800,
                                  color: hasItems
                                      ? Colors.white
                                      : Colors.white38)
                              .merge(BizBiteTheme.numeral),
                        ),
                        if (hasItems)
                          Row(
                            children: [
                              const Icon(Icons.table_bar_rounded,
                                  size: 12, color: BizBiteTheme.brand),
                              const SizedBox(width: 4),
                              Text(
                                cart.orderType.label,
                                style: const TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                  color: BizBiteTheme.brand,
                                ),
                              ),
                            ],
                          ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              SizedBox(
                height: 48,
                child: FilledButton.icon(
                  onPressed: _settling || !hasItems ? null : _settle,
                  style: FilledButton.styleFrom(
                    backgroundColor: BizBiteTheme.brand,
                    foregroundColor: Colors.white,
                    disabledBackgroundColor: Colors.white12,
                    disabledForegroundColor: Colors.white38,
                    padding: const EdgeInsets.symmetric(horizontal: 14),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                  icon: _settling
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(
                              strokeWidth: 2, color: Colors.white),
                        )
                      : const Icon(Icons.print_rounded, size: 18),
                  label: Text(
                    _settling ? 'Processing…' : 'Process & Print',
                    style: const TextStyle(
                        fontSize: 13, fontWeight: FontWeight.w800),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  /// Full bill as a modal bottom sheet (narrow layout).
  void _openBillSheet() {
    if (_billSheetOpen) return;
    _billSheetOpen = true;
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) {
        return FractionallySizedBox(
          heightFactor: 0.92,
          child: ListenableBuilder(
            listenable: widget.cart,
            builder: (context, _) => CartPane(
              cart: widget.cart,
              error: _error,
              settling: _settling,
              discountController: _discount,
              customerController: _customer,
              phoneController: _phone,
              upiRefController: _upiRef,
              onDiscountChanged: (value) {
                widget.cart
                    .setDiscountAmount(double.tryParse(value.trim()) ?? 0);
              },
              onCustomerChanged: widget.cart.setCustomerName,
              onPhoneChanged: widget.cart.setCustomerPhone,
              onUpiRefChanged: widget.cart.setUpiRef,
              onSettle: _settle,
            ),
          ),
        );
      },
    ).whenComplete(() => _billSheetOpen = false);
  }

  Future<void> _settle() async {
    if (_settling || widget.cart.isEmpty) return;

    setState(() {
      _settling = true;
      _error = '';
    });

    try {
      final receipt = await widget.checkout.settle(
        cart: widget.cart,
        paymentMode: widget.cart.paymentMode,
        orderType: widget.cart.orderType,
      );

      widget.cart.clear();
      // Close the bill sheet (if open) so the receipt screen takes over.
      if (_billSheetOpen && mounted) {
        Navigator.of(context, rootNavigator: true).pop();
      }
      widget.onSettled?.call(receipt);
      setState(() {
        _settling = false;
        _discount = TextEditingController();
        _customer = TextEditingController();
        _phone = TextEditingController();
        _upiRef = TextEditingController();
      });
    } on ApiException catch (error) {
      setState(() {
        _settling = false;
        _error = error.message;
      });
      // The bill sheet lives on another route and won't rebuild with this
      // state — surface the failure as a snackbar too.
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(error.message),
            backgroundColor: Theme.of(context).colorScheme.error,
          ),
        );
      }
    }
  }
}