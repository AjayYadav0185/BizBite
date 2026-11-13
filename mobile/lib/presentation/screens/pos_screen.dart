import 'package:flutter/material.dart' hide MenuController;

import '../../core/network/api_exception.dart';
import '../../core/utils/device_info.dart';
import '../../features/auth/session_controller.dart';
import '../../features/menu/menu_controller.dart';
import '../../features/ops/data/models/console_models.dart';
import '../../features/orders/cart_controller.dart';
import '../../features/orders/data/models/order_models.dart';
import '../../features/orders/order_checkout.dart';
import '../../features/orders/data/models/order_receipt_model.dart';
import '../../features/wallet/wallet_controller.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/amount.dart';
import 'cart_pane.dart';
import 'menu_pane.dart';

/// The Pay Desk billing screen — mirrors the Laravel `BillingDashboard`:
/// menu grid + live bill (order type, table, customer, coupon, UPI QR,
/// split tender), Hold / Resume and the counter checks the web enforces.
class PosScreen extends StatefulWidget {
  const PosScreen({
    super.key,
    required this.session,
    required this.menu,
    required this.cart,
    required this.checkout,
    required this.wallet,
    this.loadCampaigns,
    this.onSettled,
  });

  final SessionController session;
  final MenuController menu;
  final CartController cart;
  final OrderCheckout checkout;
  final WalletController wallet;

  /// Live coupons for the on-screen discount preview (cached offline).
  final Future<List<CampaignModel>> Function()? loadCampaigns;

  /// Fired with the receipt after a successful settlement.
  final void Function(OrderReceiptModel receipt)? onSettled;

  @override
  State<PosScreen> createState() => _PosScreenState();
}

class _PosScreenState extends State<PosScreen> {
  static const double _sidePanelBreakpoint = 840;

  final TextEditingController _search = TextEditingController();
  final CartFields _fields = CartFields();

  int? _activeCategoryId;
  String _error = '';
  bool _settling = false;
  bool _billSheetOpen = false;

  /// Idempotency key of the settlement in flight — kept across a timeout
  /// retry so the server dedupes the bill on flaky outlet Wi-Fi.
  String? _idempotencyKey;

  @override
  void initState() {
    super.initState();
    widget.wallet.load();
    _fields.loadFrom(widget.cart);
    _loadCampaigns();
  }

  Future<void> _loadCampaigns() async {
    final loader = widget.loadCampaigns;
    if (loader == null) return;
    try {
      widget.cart.setCampaigns(await loader());
    } catch (_) {
      // No coupons offline/forbidden → the server still validates codes.
    }
  }

  @override
  void dispose() {
    _search.dispose();
    _fields.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      backgroundColor: scheme.surfaceContainerLow,
      body: LayoutBuilder(
        builder: (context, constraints) {
          if (constraints.maxWidth >= _sidePanelBreakpoint) {
            return Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(child: _menu()),
                Container(
                  width: 400,
                  margin: const EdgeInsets.all(12),
                  clipBehavior: Clip.antiAlias,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: BizBiteTheme.hairline),
                    boxShadow: AppShadows.card,
                  ),
                  child: Column(
                    children: [
                      _heldStrip(),
                      Expanded(child: _cartPane()),
                    ],
                  ),
                ),
              ],
            );
          }
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
    return ListenableBuilder(
      listenable: Listenable.merge([widget.menu, widget.cart]),
      builder: (context, _) => MenuPane(
        menu: widget.menu,
        cart: widget.cart,
        searchController: _search,
        activeCategoryId: _activeCategoryId,
        onSearchChanged: (_) => setState(() {}),
        onCategoryTap: (id) => setState(() => _activeCategoryId = id),
      ),
    );
  }

  Widget _buildCartPane() => CartPane(
        cart: widget.cart,
        fields: _fields,
        wallet: widget.wallet,
        store: widget.session.store?.toReceiptStore(),
        error: _error,
        settling: _settling,
        onSettle: _settle,
        onHold: _hold,
      );

  Widget _cartPane() => ListenableBuilder(
        listenable: widget.cart,
        builder: (context, _) => _buildCartPane(),
      );

  /// "Held (n)" strip on top of the tablet bill panel.
  Widget _heldStrip() {
    return ListenableBuilder(
      listenable: widget.cart,
      builder: (context, _) {
        final n = widget.cart.held.length;
        if (n == 0) return const SizedBox.shrink();
        return Material(
          color: AppColors.warningBg,
          child: InkWell(
            onTap: _openHeld,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              child: Row(children: [
                const Icon(Icons.pause_circle_rounded, size: 18, color: AppColors.warningDeep),
                const SizedBox(width: 8),
                Text('$n bill${n == 1 ? '' : 's'} on hold',
                    style: const TextStyle(
                        fontWeight: FontWeight.w700, color: AppColors.warningDeep)),
                const Spacer(),
                const Text('View', style: TextStyle(fontWeight: FontWeight.w700, color: AppColors.warningDeep)),
              ]),
            ),
          ),
        );
      },
    );
  }

  /// Phone layout — dark bill bar: held badge, count, total, Settle.
  Widget _billBar(BuildContext context) {
    return ListenableBuilder(
      listenable: widget.cart,
      builder: (context, _) {
        final cart = widget.cart;
        final hasItems = !cart.isEmpty;
        final heldCount = cart.held.length;

        return Container(
          margin: const EdgeInsets.fromLTRB(12, 4, 12, 12),
          padding: const EdgeInsets.fromLTRB(8, 8, 8, 8),
          decoration: BoxDecoration(
            color: AppColors.bgDark,
            borderRadius: BorderRadius.circular(18),
          ),
          child: Row(
            children: [
              if (heldCount > 0)
                Badge(
                  label: Text('$heldCount'),
                  backgroundColor: AppColors.warning,
                  child: IconButton(
                    tooltip: 'Bills on hold',
                    onPressed: _openHeld,
                    icon: const Icon(Icons.pause_circle_outline_rounded, color: Colors.white),
                  ),
                ),
              Expanded(
                child: InkWell(
                  onTap: hasItems ? _openBillSheet : null,
                  borderRadius: BorderRadius.circular(12),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          hasItems
                              ? '${cart.totalQuantity} item(s) · ${cart.orderType.label}'
                              : 'Tap items to add',
                          style: TextStyle(
                              fontSize: 12.5,
                              fontWeight: FontWeight.w700,
                              color: hasItems ? Colors.white70 : Colors.white54),
                        ),
                        Text(
                          inr(cart.payable),
                          style: TextStyle(
                                  fontSize: 20,
                                  fontWeight: FontWeight.w800,
                                  color: hasItems ? Colors.white : Colors.white38)
                              .merge(BizBiteTheme.numeral),
                        ),
                        if (hasItems)
                          const Text('View bill ›',
                              style: TextStyle(
                                  fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primaryLight)),
                      ],
                    ),
                  ),
                ),
              ),
              SizedBox(
                height: 50,
                child: FilledButton.icon(
                  onPressed: _settling || !hasItems ? null : _openBillSheet,
                  style: FilledButton.styleFrom(
                    backgroundColor: BizBiteTheme.brand,
                    foregroundColor: Colors.white,
                    disabledBackgroundColor: Colors.white12,
                    disabledForegroundColor: Colors.white38,
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  icon: const Icon(Icons.receipt_long_rounded, size: 18),
                  label: const Text('Bill & pay',
                      style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800)),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  void _openBillSheet() {
    if (_billSheetOpen) return;
    _billSheetOpen = true;
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) => FractionallySizedBox(
        heightFactor: 0.94,
        child: ListenableBuilder(
          listenable: widget.cart,
          builder: (context, _) => _buildCartPane(),
        ),
      ),
    ).whenComplete(() => _billSheetOpen = false);
  }

  void _snack(String message, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(message),
      backgroundColor: error ? Theme.of(context).colorScheme.error : null,
    ));
  }

  void _hold() {
    final problem = widget.cart.hold();
    if (problem != null) {
      _snack(problem, error: true);
      return;
    }
    _fields.loadFrom(widget.cart);
    if (_billSheetOpen) Navigator.of(context, rootNavigator: true).pop();
    _snack('Bill put on hold. Resume it from "Held".');
  }

  Future<void> _openHeld() async {
    await showModalBottomSheet<void>(
      context: context,
      builder: (sheet) => ListenableBuilder(
        listenable: widget.cart,
        builder: (context, _) {
          final held = widget.cart.held;
          return SafeArea(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Padding(
                  padding: EdgeInsets.fromLTRB(20, 0, 20, 8),
                  child: Text('Bills on hold',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
                ),
                if (held.isEmpty)
                  const Padding(
                    padding: EdgeInsets.all(32),
                    child: Text('No bills on hold.', textAlign: TextAlign.center),
                  ),
                for (final bill in held)
                  ListTile(
                    leading: const Icon(Icons.receipt_rounded),
                    title: Text(bill.label, style: const TextStyle(fontWeight: FontWeight.w700)),
                    subtitle: Text(
                        '${bill.count} items · held ${TimeOfDay.fromDateTime(bill.heldAt).format(context)}'),
                    trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                      Text(inr(bill.total), style: BizBiteTheme.numeral),
                      IconButton(
                        tooltip: 'Discard',
                        icon: const Icon(Icons.delete_outline_rounded, color: AppColors.error),
                        onPressed: () => widget.cart.discardHeld(bill.id),
                      ),
                    ]),
                    onTap: () {
                      widget.cart.resume(bill.id);
                      _fields.loadFrom(widget.cart);
                      Navigator.of(sheet).pop();
                      _snack('Resumed ${bill.label}.');
                    },
                  ),
                const SizedBox(height: 12),
              ],
            ),
          );
        },
      ),
    );
  }

  Future<void> _settle() async {
    if (_settling || widget.cart.isEmpty) return;

    final problem = widget.cart.validate();
    if (problem != null) {
      setState(() => _error = problem);
      _snack(problem, error: true);
      return;
    }

    _idempotencyKey ??= DeviceInfo.newIdempotencyKey();
    setState(() {
      _settling = true;
      _error = '';
    });

    try {
      final receipt = await widget.checkout.settle(
        cart: widget.cart,
        paymentMode: widget.cart.paymentMode,
        orderType: widget.cart.orderType,
        idempotencyKey: _idempotencyKey,
        store: widget.session.store?.toReceiptStore(),
        cashier: widget.session.user?.name ?? '',
      );

      widget.cart.clear();
      _fields.loadFrom(widget.cart);
      if (_billSheetOpen && mounted) {
        Navigator.of(context, rootNavigator: true).pop();
      }
      widget.onSettled?.call(receipt);
      _idempotencyKey = null;

      if (mounted) setState(() => _settling = false);
    } on ApiException catch (error) {
      _failSettlement(error.message);
    } catch (_) {
      _failSettlement('Checkout failed unexpectedly. Please review the bill and try again.');
    }
  }

  void _failSettlement(String message) {
    _idempotencyKey = null;
    if (!mounted) return;
    setState(() {
      _settling = false;
      _error = message;
    });
    _snack(message, error: true);
  }
}
