import 'package:flutter/material.dart' hide MenuController;

import '../features/auth/session_controller.dart';
import '../features/menu/menu_controller.dart';
import '../features/orders/cart_controller.dart';
import '../features/orders/order_checkout.dart';
import '../features/orders/order_flow_controller.dart';
import '../features/orders/data/models/order_receipt_model.dart';
import 'screens/admin_screen.dart';
import 'screens/pos_screen.dart';
import 'services/receipt_printer.dart';

/// Signed-in shell: a bottom navigation bar switching between the POS
/// billing screen (everyone) and the read-only store admin screen.
///
/// Mirrors the Laravel portal split: `/pos` for all staff, `/admin` for the
/// owner — except the mobile console lets cashiers see a read-only store
/// overview so the till never needs a separate device.
class HomeScreen extends StatefulWidget {
  const HomeScreen({
    super.key,
    required this.session,
    required this.menu,
    required this.cart,
    required this.orderFlow,
    required this.checkout,
    required this.printer,
  });

  final SessionController session;
  final MenuController menu;
  final CartController cart;
  final OrderFlowController orderFlow;
  final OrderCheckout checkout;
  final ReceiptPrinter printer;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _tabIndex = 0;

  @override
  void initState() {
    super.initState();
    // Pre-fetch the menu once per signed-in shell.
    widget.menu.load();
  }

  void _onLogout() {
    widget.session.logout();
  }

  @override
  Widget build(BuildContext context) {
    final storeName = widget.session.store?.name ?? 'BizBite';

    final PosScreen pos = PosScreen(
      session: widget.session,
      menu: widget.menu,
      cart: widget.cart,
      checkout: widget.checkout,
      onSettled: (OrderReceiptModel receipt) {
        // Root router listens to orderFlow and swaps in the receipt screen.
        widget.orderFlow.showReceipt(receipt);
      },
    );
    final AdminScreen admin = AdminScreen(
      session: widget.session,
      menu: widget.menu,
    );

    return Scaffold(
      appBar: AppBar(
        title: Text('$storeName — Pay Desk'),
        actions: [
          IconButton(
            icon: Icon(Icons.logout, size: 20),
            tooltip: 'Sign out',
            onPressed: _onLogout,
          ),
        ],
      ),
      body: _tabIndex == 0 ? pos : admin,
      bottomNavigationBar: BottomNavigationBar(
        items: [
          BottomNavigationBarItem(
            icon: Icon(Icons.point_of_sale),
            label: 'Billing',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.storefront),
            label: 'Store',
          ),
        ],
        currentIndex: _tabIndex,
        onTap: (index) {
          setState(() {
            _tabIndex = index;
          });
        },
      ),
    );
  }
}