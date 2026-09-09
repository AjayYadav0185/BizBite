import 'package:flutter/material.dart' hide MenuController;

import '../../core/network/api_exception.dart';
import '../../features/auth/session_controller.dart';
import '../../features/menu/menu_controller.dart';
import '../../features/orders/cart_controller.dart';
import '../../features/orders/order_checkout.dart';
import '../../features/orders/data/models/order_receipt_model.dart';
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
  final TextEditingController _search = TextEditingController();
  TextEditingController _discount = TextEditingController();
  TextEditingController _customer = TextEditingController();
  TextEditingController _phone = TextEditingController();
  TextEditingController _upiRef = TextEditingController();

  int? _activeCategoryId;
  String _error = '';
  bool _settling = false;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Scaffold(
      backgroundColor: scheme.surfaceContainerLow,
      body: Column(
        children: [
          Expanded(
            child: ListenableBuilder(
              listenable: widget.menu,
              builder: (context, child) {
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
            ),
          ),
          SizedBox(height: 4),
          ListenableBuilder(
            listenable: widget.cart,
            builder: (context, child) {
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
                onCustomerChanged: (value) {
                  widget.cart.setCustomerName(value);
                },
                onPhoneChanged: (value) {
                  widget.cart.setCustomerPhone(value);
                },
                onUpiRefChanged: (value) {
                  widget.cart.setUpiRef(value);
                },
                onSettle: _settle,
              );
            },
          ),
        ],
      ),
    );
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
    }
  }
}