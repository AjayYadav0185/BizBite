import 'package:flutter/material.dart' hide MenuController;

import '../../features/auth/session_controller.dart';
import '../../features/menu/menu_controller.dart';
import '../widgets/amount.dart';

/// Read-only store console for the owner/cashier.
///
/// Mirrors the Owner Admin Portal's Store Setup + Menu tabs (Laravel
/// `MenuManager` / store profile) as a compact, mobile-friendly overview.
/// Full CRUD stays in the web console — this screen is for quick boarding.
class AdminScreen extends StatelessWidget {
  const AdminScreen({super.key, required this.session, required this.menu});

  final SessionController session;
  final MenuController menu;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final store = session.store;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _storeCard(theme, scheme, store),
        SizedBox(height: 12),
        Text('Menu overview', style: theme.textTheme.titleMedium),
        SizedBox(height: 8),
        Expanded(
          child: ListenableBuilder(
            listenable: menu,
            builder: (context, child) {
              if (menu.menu == null) {
                return Text(
                  menu.loading ? 'Loading…' : 'Menu unavailable',
                  style: theme.textTheme.bodyMedium,
                );
              }

              final rows = <Widget>[];
              for (final category in menu.menu!.categories) {
                final items = menu.menu!.itemsIn(category.id);
                rows.add(
                  Container(
                    margin: EdgeInsets.symmetric(vertical: 6),
                    padding: EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: scheme.surfaceContainer,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(category.name,
                            style: theme.textTheme.titleSmall,
                            selectionColor: scheme.primary),
                        SizedBox(height: 4),
                        for (final item in items.take(8))
                          Row(
                            children: [
                              Expanded(
                                child: Text(item.name,
                                    style: theme.textTheme.bodyMedium),
                              ),
                              Text(inr(item.price),
                                  style: theme.textTheme.bodyMedium,
                                  selectionColor: scheme.onSurfaceVariant),
                            ],
                          ),
                      ],
                    ),
                  ),
                );
              }
              return ListView(children: rows);
            },
          ),
        ),
      ],
    );
  }

  Widget _storeCard(
      ThemeData theme, ColorScheme scheme, dynamic store) {
    final name = store?.name ?? 'BizBite';
    final location =
        (store?.location ?? '').toString();
    final phone = (store?.phone ?? '').toString();
    final upi = (store?.upiVpa ?? '').toString();

    return Container(
      padding: EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: scheme.primaryContainer,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(name, style: theme.textTheme.titleLarge),
          if (location.isNotEmpty)
            Text(location, style: theme.textTheme.bodySmall),
          if (phone.isNotEmpty)
            Row(
              spacing: 6,
              children: [
                Icon(Icons.phone, size: 16, color: scheme.onPrimaryContainer),
                Text(phone, style: theme.textTheme.bodySmall),
              ],
            ),
          if (upi.isNotEmpty)
            Row(
              spacing: 6,
              children: [
                Icon(Icons.qr_code, size: 16, color: scheme.onPrimaryContainer),
                Text(upi, style: theme.textTheme.bodySmall),
              ],
            ),
          if (store != null)
            Text('Currency: ${store.currency}',
                style: theme.textTheme.bodySmall),
        ],
      ),
    );
  }
}