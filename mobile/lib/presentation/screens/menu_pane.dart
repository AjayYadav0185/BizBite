import 'package:flutter/material.dart' hide MenuController;

import '../../features/menu/data/models/food_item_model.dart';
import '../../features/menu/menu_controller.dart';
import '../../features/orders/cart_controller.dart';
import '../widgets/amount.dart';
import '../widgets/status_banner.dart';

/// Searchable, category-filtered menu grid — the left side of the Pay Desk.
///
/// Reactive: this pane is the output of an `ListenableBuilder(listenable:
/// menuController)`, so a background menu load re-renders it automatically.
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

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    if (menu.menu == null) {
      return Center(
        child: Column(
          children: [
            Icon(Icons.storefront, size: 48, color: scheme.primary),
            SizedBox(height: 8),
            Text(
              menu.loading ? 'Loading menu…' : (menu.error ?? 'Menu unavailable'),
              style: theme.textTheme.bodyLarge,
              selectionColor: scheme.onSurfaceVariant,
            ),
          ],
        ),
      );
    }

    final menuData = menu.menu!;
    final query = searchController.text.trim().toLowerCase();

    List<FoodItemModel> items = menuData.items;
    if (activeCategoryId != null) {
      items = items
          .where((item) => item.categoryId == activeCategoryId)
          .toList();
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
        TextField(
          controller: searchController,
          decoration: InputDecoration(
            labelText: 'Search menu…',
            icon: Icon(Icons.search),
            prefixIcon: Icon(Icons.search),
          ),
          onChanged: onSearchChanged,
        ),
        SizedBox(height: 4),
        StatusBanner(text: menu.error ?? '', error: true),
        // Category chips — 'All' + each active category.
        ListenableBuilder(
          listenable: menu,
          builder: (context, child) {
            return Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _categoryChips(theme, scheme),
            );
          },
        ),
        SizedBox(height: 8),
        Expanded(
          child: ListView(
            padding: EdgeInsets.all(12),
            children: items.map((item) {
              final inCart = cart.lines.any((line) => line.foodItem.id == item.id);
              return _itemCard(theme, scheme, item, inCart);
            }).toList(),
          ),
        ),
      ],
    );
  }

  List<Widget> _categoryChips(ThemeData theme, ColorScheme scheme) {
    final chips = <Widget>[
      _chip(
        theme,
        scheme,
        label: 'All',
        selected: activeCategoryId == null,
        onTap: () => onCategoryTap?.call(null),
      ),
    ];
    for (final category in menu.menu!.categories) {
      chips.add(
        _chip(
          theme,
          scheme,
          label: category.name,
          selected: activeCategoryId == category.id,
          onTap: () => onCategoryTap?.call(category.id),
        ),
      );
    }
    return chips;
  }

  Widget _chip(ThemeData theme, ColorScheme scheme,
      {required String label, required bool selected, required VoidCallback onTap}) {
    if (selected) {
      return FilledButton(
        onPressed: onTap,
        child: Text(label, style: theme.textTheme.labelLarge),
      );
    }
    return OutlinedButton(
      onPressed: onTap,
      child: Text(label, style: theme.textTheme.labelLarge),
    );
  }

  Widget _itemCard(ThemeData theme, ColorScheme scheme, FoodItemModel item, bool inCart) {
    final price = inr(item.price);
    return Container(
      margin: EdgeInsets.symmetric(vertical: 8),
      padding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      decoration: BoxDecoration(
        color: inCart ? scheme.secondaryContainer : scheme.surfaceContainer,
        borderRadius: BorderRadius.circular(12),
      ),
      child: FilledButton(
        onPressed: () => cart.add(item),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(item.name, style: theme.textTheme.bodyLarge),
            Text(price,
                style: theme.textTheme.labelLarge,
                selectionColor: scheme.primary),
          ],
        ),
      ),
    );
  }
}