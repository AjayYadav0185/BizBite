import 'package:flutter/material.dart' hide MenuController;

import '../../features/menu/data/models/food_item_model.dart';
import '../../features/menu/menu_controller.dart';
import '../../features/orders/cart_controller.dart';
import '../../features/orders/data/models/order_models.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/amount.dart';
import '../widgets/status_banner.dart';

/// Screen 1 — Home Dashboard / Quick Order Grid (the "menu" half of the POS).
///
/// Layout top-to-bottom:
///   1. Order-type segmented toggle (Dine-In / Takeaway / Parcel / Delivery)
///   2. Search field
///   3. Horizontally scrolling category chips
///   4. Food card grid with a prominent quick-add "+" button
///
/// Reactive: rendered inside `ListenableBuilder(listenable: menuController)`,
/// so a background menu load re-renders it automatically; cart badges are
/// driven by the cart controller.
class MenuPane extends StatelessWidget {
  const MenuPane({
    super.key,
    required this.menu,
    required this.cart,
    required this.searchController,
    required this.activeCategoryId,
    required this.onSearchChanged,
    required this.onCategoryTap,
  });

  final MenuController menu;
  final CartController cart;
  final TextEditingController searchController;
  final int? activeCategoryId;
  final void Function(String value)? onSearchChanged;
  final void Function(int? categoryId)? onCategoryTap;

  /// Warm thumbnail hues, indexed by category id so each food family keeps
  /// a stable, appetizing color across sessions.
  static const _thumbFills = [
    Color(0xFFFFF3E0), // warm cream
    Color(0xFFFFEBEE), // soft rose
    Color(0xFFE8F5E9), // mint cream
    Color(0xFFFFF8E1), // butter
    Color(0xFFEDE7F6), // lavender
    Color(0xFFE0F7FA), // aqua
  ];

  static const _thumbInks = [
    Color(0xFFBF360C),
    Color(0xFFAD1457),
    Color(0xFF2E7D32),
    Color(0xFFEF6C00),
    Color(0xFF4527A0),
    Color(0xFF00838F),
  ];

  /// Maps a category/item name to a recognizable glyph (keyword heuristics).
  static IconData _categoryIcon(String name) {
    final key = name.toLowerCase();
    if (key.contains('bever') || key.contains('drink') || key.contains('chai')) {
      return Icons.local_cafe_rounded;
    }
    if (key.contains('dessert') || key.contains('sweet') || key.contains('ice')) {
      return Icons.icecream_rounded;
    }
    if (key.contains('fast') || key.contains('burger') || key.contains('pizza')) {
      return Icons.fastfood_rounded;
    }
    if (key.contains('snack')) return Icons.cookie_rounded;
    if (key.contains('main') || key.contains('thali') || key.contains('meal')) {
      return Icons.dinner_dining_rounded;
    }
    return Icons.restaurant_menu_rounded;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    if (menu.menu == null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (menu.loading)
              const SizedBox(
                width: 36,
                height: 36,
                child: CircularProgressIndicator(strokeWidth: 3),
              )
            else
              Icon(Icons.storefront_rounded, size: 48, color: scheme.primary),
            const SizedBox(height: 12),
            Text(
              menu.loading ? 'Loading menu…' : (menu.error ?? 'Menu unavailable'),
              style: theme.textTheme.bodyLarge
                  ?.copyWith(color: scheme.onSurfaceVariant),
            ),
          ],
        ),
      );
    }

    final menuData = menu.menu!;
    final query = searchController.text.trim().toLowerCase();

    List<FoodItemModel> items = menuData.items;
    if (activeCategoryId != null) {
      items =
          items.where((item) => item.categoryId == activeCategoryId).toList();
    }
    if (query.isNotEmpty) {
      items = items
          .where((item) => item.name.toLowerCase().contains(query))
          .toList();
    }
    items = items.take(60).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _orderTypeToggle(),
              const SizedBox(height: 10),
              TextField(
                controller: searchController,
                onChanged: onSearchChanged,
                textInputAction: TextInputAction.search,
                decoration: const InputDecoration(
                  hintText: 'Search menu…',
                  prefixIcon: Icon(Icons.search_rounded),
                ),
              ),
              const SizedBox(height: 10),
              StatusBanner(text: menu.error ?? '', error: true),
              SizedBox(
                height: 38,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  clipBehavior: Clip.none,
                  children: _categoryChips(context),
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: items.isEmpty
              ? Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.search_off_rounded,
                          size: 40, color: scheme.outline),
                      const SizedBox(height: 8),
                      Text(
                        'No items match this filter',
                        style: theme.textTheme.bodyMedium
                            ?.copyWith(color: scheme.onSurfaceVariant),
                      ),
                    ],
                  ),
                )
              : LayoutBuilder(
                  builder: (context, constraints) {
                    // 2 columns on phones, more on tablets — ~176dp cards.
                    final columns =
                        (constraints.maxWidth / 176).floor().clamp(2, 5);
                    return GridView.builder(
                      padding: const EdgeInsets.fromLTRB(12, 4, 12, 24),
                      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: columns,
                        mainAxisSpacing: 10,
                        crossAxisSpacing: 10,
                        childAspectRatio: 0.82,
                      ),
                      itemCount: items.length,
                      itemBuilder: (context, index) {
                        final item = items[index];
                        final qtyInCart = cart.lines
                            .where((line) => line.foodItem.id == item.id)
                            .fold(0, (sum, line) => sum + line.quantity);
                        return _ItemCard(
                          item: item,
                          quantityInCart: qtyInCart,
                          icon: _categoryIcon(item.name),
                          fill:
                              _thumbFills[item.categoryId % _thumbFills.length],
                          ink: _thumbInks[item.categoryId % _thumbInks.length],
                          onTap: () => cart.add(item),
                        );
                      },
                    );
                  },
                ),
        ),
      ],
    );
  }

  /// Quick order-type toggle. Segments: Dine-In / Takeaway / Delivery, plus
  /// Parcel which the backend `POST /api/orders` contract also accepts.
  Widget _orderTypeToggle() {
    return ListenableBuilder(
      listenable: cart,
      builder: (context, _) {
        return SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          clipBehavior: Clip.none,
          child: SegmentedButton<OrderType>(
            showSelectedIcon: false,
            segments: const [
              ButtonSegment(
                value: OrderType.dineIn,
                icon: Icon(Icons.table_restaurant_rounded, size: 16),
                label: Text('Dine-In'),
              ),
              ButtonSegment(
                value: OrderType.takeaway,
                icon: Icon(Icons.takeout_dining_rounded, size: 16),
                label: Text('Takeaway'),
              ),
              ButtonSegment(
                value: OrderType.delivery,
                icon: Icon(Icons.moped_rounded, size: 16),
                label: Text('Delivery'),
              ),
              ButtonSegment(
                value: OrderType.parcel,
                icon: Icon(Icons.shopping_bag_rounded, size: 16),
                label: Text('Parcel'),
              ),
            ],
            selected: {cart.orderType},
            onSelectionChanged: (selection) =>
                cart.setOrderType(selection.first),
          ),
        );
      },
    );
  }

  List<Widget> _categoryChips(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    Widget chip({
      required String label,
      required bool selected,
      required VoidCallback onTap,
      IconData? icon,
    }) {
      return Padding(
        padding: const EdgeInsets.only(right: 8),
        child: Material(
          color: selected ? BizBiteTheme.brand : Colors.white,
          borderRadius: BorderRadius.circular(19),
          child: InkWell(
            onTap: onTap,
            borderRadius: BorderRadius.circular(19),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14),
              alignment: Alignment.center,
              decoration: ShapeDecoration(
                shape: StadiumBorder(
                  side: BorderSide(
                    color:
                        selected ? BizBiteTheme.brand : BizBiteTheme.hairline,
                  ),
                ),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (icon != null) ...[
                    Icon(icon,
                        size: 15,
                        color:
                            selected ? Colors.white : scheme.onSurfaceVariant),
                    const SizedBox(width: 5),
                  ],
                  Text(
                    label,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: selected ? Colors.white : scheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    }

    final chips = <Widget>[
      chip(
        label: 'All',
        selected: activeCategoryId == null,
        onTap: () => onCategoryTap?.call(null),
        icon: Icons.grid_view_rounded,
      ),
    ];
    for (final category in menu.menu!.categories) {
      chips.add(
        chip(
          label: category.name,
          selected: activeCategoryId == category.id,
          onTap: () => onCategoryTap?.call(category.id),
          icon: _categoryIcon(category.name),
        ),
      );
    }
    return chips;
  }
}

/// Food item card — compact thumbnail, two-line name, bold tabular price and
/// a 34dp quick-add "+" target. The WHOLE card is tappable so a cashier can
/// rapid-fire items without aiming at the small button.
class _ItemCard extends StatelessWidget {
  const _ItemCard({
    required this.item,
    required this.quantityInCart,
    required this.icon,
    required this.fill,
    required this.ink,
    required this.onTap,
  });

  final FoodItemModel item;
  final int quantityInCart;
  final IconData icon;
  final Color fill;
  final Color ink;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final inCart = quantityInCart > 0;

    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.all(10),
          decoration: ShapeDecoration(
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
              side: BorderSide(
                color: inCart ? BizBiteTheme.brand : BizBiteTheme.hairline,
                width: inCart ? 1.4 : 1,
              ),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Stack(
                clipBehavior: Clip.none,
                children: [
                  Container(
                    height: 84,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: fill,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(icon, size: 34, color: ink),
                  ),
                  if (inCart)
                    Positioned(
                      top: -5,
                      right: -5,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 7, vertical: 2),
                        decoration: BoxDecoration(
                          color: BizBiteTheme.brand,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: Colors.white, width: 1.5),
                        ),
                        child: Text(
                          '$quantityInCart',
                          style: BizBiteTheme.numeral.copyWith(
                            color: Colors.white,
                            fontSize: 11.5,
                          ),
                        ),
                      ),
                    ),
                ],
              ),
              const Spacer(),
              Text(
                item.name,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 13.5,
                  height: 1.15,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      inr(item.price),
                      style: theme.textTheme.titleSmall
                          ?.copyWith(color: BizBiteTheme.brandDeep)
                          .merge(BizBiteTheme.numeral.copyWith(fontSize: 14)),
                    ),
                  ),
                  SizedBox(
                    width: 34,
                    height: 34,
                    child: IconButton.filled(
                      onPressed: onTap,
                      padding: EdgeInsets.zero,
                      iconSize: 20,
                      style: IconButton.styleFrom(
                        backgroundColor:
                            inCart ? BizBiteTheme.brandDeep : scheme.primary,
                        foregroundColor: Colors.white,
                        shape: const CircleBorder(),
                      ),
                      icon: const Icon(Icons.add_rounded),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}