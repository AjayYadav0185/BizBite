import 'package:flutter/material.dart' hide MenuController;

import '../features/auth/session_controller.dart';
import '../features/menu/menu_controller.dart';
import '../features/orders/cart_controller.dart';
import '../features/orders/order_checkout.dart';
import '../features/orders/order_flow_controller.dart';
import '../features/orders/data/models/order_receipt_model.dart';
import 'screens/admin_screen.dart';
import 'screens/pos_screen.dart';
import 'screens/profile_screen.dart';
import 'services/receipt_printer.dart';
import 'theme/bizbite_theme.dart';

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
      // Spec §7 — custom white bar + top shadow, 5-slot language.
      // Active = ink circle + white icon, label 9.5px.
      appBar: BizAppBar(
        title: storeName,
        subtitle: _tabIndex == 0 ? 'Pay Desk' : 'Store Console',
        actions: [
          ..._userChip(),
          IconButton(
            icon: const Icon(Icons.logout_rounded, size: 20),
            tooltip: 'Sign out',
            onPressed: _onLogout,
          ),
        ],
      ),
      drawer: _navDrawer(context, storeName: storeName),
      body: Container(
        decoration: const BoxDecoration(gradient: AppGradients.page),
        child: _tabIndex == 0 ? pos : admin,
      ),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: AppShadows.bar,
        ),
        child: BottomNavigationBar(
          items: const [
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
          onTap: _selectTab,
        ),
      ),
    );
  }

  void _selectTab(int index) {
    setState(() => _tabIndex = index);
  }

  /// Signed-in user chip for the app bar (avatar initial + first name) so
  /// the active login is visible on every launch — including cold starts
  /// where the session was restored from the device vault.
  List<Widget> _userChip() {
    final user = widget.session.user;
    if (user == null) return const [];

    final name = user.name.trim();
    final firstName = name.split(' ').first;
    final initial = firstName.isEmpty ? '?' : firstName[0].toUpperCase();

    return [
      Container(
        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
        decoration: BoxDecoration(
          color: AppColors.surfaceMuted,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 26,
              height: 26,
              alignment: Alignment.center,
              decoration: const BoxDecoration(
                gradient: AppGradients.brandMain,
                shape: BoxShape.circle,
              ),
              child: Text(
                initial,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                ),
              ),
            ),
            const SizedBox(width: 6),
            Text(
              firstName,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: AppColors.ink,
              ),
            ),
            const SizedBox(width: 6),
          ],
        ),
      ),
      const SizedBox(width: 6),
    ];
  }

  /// Side menu opened by the round menu button in `BizAppBar` (the app bar
  /// calls `Scaffold.of(context).openDrawer()` when it cannot pop).
  Widget _navDrawer(BuildContext context, {required String storeName}) {
    final user = widget.session.user;
    final isAdmin = widget.session.isAdmin;
    final name = (user?.name ?? '').trim();
    final initial = name.isEmpty ? 'B' : name[0].toUpperCase();

    return Drawer(
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.horizontal(right: Radius.circular(26)),
      ),
      child: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Header — brand + store + signed-in identity.
            Container(
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 16),
              decoration: const BoxDecoration(
                gradient: AppGradients.page,
                border: Border(
                  bottom: BorderSide(color: BizBiteTheme.hairline),
                ),
              ),
              child: Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: const BoxDecoration(
                      gradient: AppGradients.brandMain,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.storefront_rounded,
                      color: Colors.white,
                      size: 22,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          storeName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 15.5,
                            fontWeight: FontWeight.w800,
                            color: AppColors.ink,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          user?.email ?? 'Signed-in staff',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w500,
                            color: AppColors.muted,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 10),
            const Padding(
              padding: EdgeInsets.fromLTRB(20, 8, 20, 4),
              child: Text(
                'NAVIGATE',
                style: TextStyle(
                  fontSize: 10.5,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 1.2,
                  color: AppColors.muted,
                ),
              ),
            ),
            _navTile(
              context,
              icon: Icons.point_of_sale_rounded,
              title: 'Billing',
              subtitle: 'Pay desk & quick orders',
              selected: _tabIndex == 0,
              onTap: () {
                _selectTab(0);
                Navigator.of(context).pop();
              },
            ),
            _navTile(
              context,
              icon: Icons.storefront_rounded,
              title: 'Store',
              subtitle: isAdmin
                  ? 'Manage menu & categories'
                  : 'Read-only store overview',
              selected: _tabIndex == 1,
              trailing: isAdmin ? null : _viewOnlyChip(),
              onTap: () {
                _selectTab(1);
                Navigator.of(context).pop();
              },
            ),
            const SizedBox(height: 10),
            _navTile(
              context,
              icon: Icons.person_outline_rounded,
              title: 'My Profile',
              subtitle: 'Edit name, phone & password',
              selected: false,
              onTap: () {
                Navigator.of(context).pop();
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => ProfileScreen(session: widget.session),
                  ),
                );
              },
            ),
            const Spacer(),
            const Divider(height: 1, color: BizBiteTheme.hairline),
            // Signed-in staff summary.
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 14, 20, 4),
              child: Row(
                children: [
                  Container(
                    width: 34,
                    height: 34,
                    alignment: Alignment.center,
                    decoration: const BoxDecoration(
                      gradient: AppGradients.brandMain,
                      shape: BoxShape.circle,
                    ),
                    child: Text(
                      initial,
                      style: const TextStyle(
                        fontSize: 13.5,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          name.isEmpty ? 'Staff' : name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w800,
                            color: AppColors.ink,
                          ),
                        ),
                        Text(
                          isAdmin ? 'Admin' : 'Cashier',
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: AppColors.muted,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 2, 12, 12),
              child: TextButton.icon(
                onPressed: _onLogout,
                style: TextButton.styleFrom(
                  foregroundColor: AppColors.error,
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                icon: const Icon(Icons.logout_rounded, size: 18),
                label: const Text(
                  'Sign out',
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _navTile(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required bool selected,
    required VoidCallback onTap,
    Widget? trailing,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 3),
      child: Material(
        color: selected ? AppColors.infoBg : Colors.transparent,
        borderRadius: BorderRadius.circular(14),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(14),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: selected
                ? ShapeDecoration(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                      side: const BorderSide(color: BizBiteTheme.brand),
                    ),
                  )
                : null,
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: selected
                        ? BizBiteTheme.brand
                        : AppColors.surfaceMuted,
                    borderRadius: BorderRadius.circular(11),
                  ),
                  child: Icon(
                    icon,
                    size: 18,
                    color: selected ? Colors.white : AppColors.slate500,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: TextStyle(
                          fontSize: 13.5,
                          fontWeight: FontWeight.w800,
                          color: selected
                              ? AppColors.primaryDeep
                              : AppColors.ink,
                        ),
                      ),
                      const SizedBox(height: 1),
                      Text(
                        subtitle,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                          color: AppColors.muted,
                        ),
                      ),
                    ],
                  ),
                ),
                ?trailing,
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _viewOnlyChip() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(8),
      ),
      child: const Text(
        'VIEW ONLY',
        style: TextStyle(
          fontSize: 9,
          fontWeight: FontWeight.w800,
          letterSpacing: 0.6,
          color: AppColors.slate500,
        ),
      ),
    );
  }
}
