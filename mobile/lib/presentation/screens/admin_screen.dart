import 'package:flutter/material.dart' hide MenuController;
import 'package:flutter/services.dart';

import '../../features/auth/data/models/store_profile_model.dart';
import '../../features/auth/data/models/user_model.dart';
import '../../features/auth/session_controller.dart';
import '../../features/menu/menu_controller.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/amount.dart';
import '../widgets/category_visuals.dart';

/// Screen 4 — Store console (read-only) for the owner/cashier.
///
/// Mirrors the Owner Admin Portal's Store Setup + Menu tabs (Laravel
/// `MenuManager` / store profile) as a compact, mobile-friendly overview.
/// Full CRUD stays in the web console — this screen is for quick boarding.
///
/// Layout top-to-bottom:
///   1. Dark store hero card — monogram avatar, name, location, phone,
///      tap-to-copy UPI VPA, currency / GSTIN / FSSAI chips
///   2. Signed-in staff card — avatar, name, email, role badge
///   3. Menu overview — one expandable card per category with item rows
///      and tabular-figure prices
class AdminScreen extends StatelessWidget {
  const AdminScreen({super.key, required this.session, required this.menu});

  final SessionController session;
  final MenuController menu;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 24),
      children: [
        _storeHero(context, session.store),
        if (session.user != null) ...[
          const SizedBox(height: 12),
          _staffCard(context, session.user!),
        ],
        const SizedBox(height: 12),
        _menuSection(context),
      ],
    );
  }

  /// Menu overview — one expandable card per category, each row showing the
  /// item name and its price in bold tabular numerals.
  Widget _menuSection(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 4),
          child: Text(
            'Menu overview',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
        ),
        const SizedBox(height: 8),
        ListenableBuilder(
          listenable: menu,
          builder: (context, _) {
            if (menu.menu == null) {
              return _menuStateCard(
                context,
                icon: menu.loading
                    ? Icons.hourglass_top_rounded
                    : Icons.storefront_rounded,
                text: menu.loading ? 'Loading menu…' : 'Menu unavailable',
              );
            }

            final data = menu.menu!;
            if (data.categories.isEmpty) {
              return _menuStateCard(
                context,
                icon: Icons.category_rounded,
                text: 'No categories on the menu yet',
              );
            }

            return Column(
              children: [
                for (final category in data.categories)
                  _categoryCard(
                    context,
                    categoryId: category.id,
                    name: category.name,
                    items: data.itemsIn(category.id),
                  ),
              ],
            );
          },
        ),
        const SizedBox(height: 4),
        Text(
          'Full menu & store editing lives in the web console.',
          style: theme.textTheme.bodySmall?.copyWith(color: scheme.outline),
        ),
      ],
    );
  }

  // --- Store hero -----------------------------------------------------------

  /// Dark hero card with the store's identity. UPI VPA is tap-to-copy so a
  /// cashier can share it with a walk-in customer instantly.
  Widget _storeHero(BuildContext context, StoreProfileModel? store) {
    final name = (store?.name ?? '').trim();
    final displayName = name.isNotEmpty ? name : 'BizBite';
    final location = store?.location ?? '';
    final phone = store?.phone ?? '';
    final upi = store?.upiVpa ?? '';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: BizBiteTheme.inkDark,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 46,
                height: 46,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: BizBiteTheme.brand,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Text(
                  displayName[0].toUpperCase(),
                  style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w800,
                      color: Colors.white),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      displayName,
                      style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                          color: Colors.white),
                    ),
                    if (location.isNotEmpty)
                      Text(
                        location,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 12.5,
                          fontWeight: FontWeight.w500,
                          color: Colors.white.withValues(alpha: 0.7),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
          if (phone.isNotEmpty || upi.isNotEmpty) ...[
            const SizedBox(height: 12),
            if (phone.isNotEmpty) _heroRow(Icons.phone_rounded, phone),
            if (upi.isNotEmpty) ...[
              const SizedBox(height: 6),
              _heroRow(
                Icons.qr_code_rounded,
                upi,
                onCopy: () => _copyToClipboard(context, upi),
              ),
            ],
          ],
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _metaChip(Icons.currency_exchange_rounded,
                  store?.currency ?? 'INR'),
              if ((store?.gstin ?? '').isNotEmpty)
                _metaChip(Icons.receipt_rounded, 'GSTIN ${store!.gstin}'),
              if ((store?.fssaiLicense ?? '').isNotEmpty)
                _metaChip(Icons.verified_user_rounded,
                    'FSSAI ${store!.fssaiLicense}'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _heroRow(IconData icon, String value, {VoidCallback? onCopy}) {
    return Row(
      children: [
        Icon(icon, size: 15, color: BizBiteTheme.brand),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Colors.white)
                .merge(BizBiteTheme.numeral),
          ),
        ),
        if (onCopy != null)
          InkWell(
            onTap: onCopy,
            borderRadius: BorderRadius.circular(8),
            child: Padding(
              padding: const EdgeInsets.all(4),
              child: Icon(Icons.copy_rounded,
                  size: 16, color: Colors.white.withValues(alpha: 0.55)),
            ),
          ),
      ],
    );
  }

  void _copyToClipboard(BuildContext context, String value) {
    Clipboard.setData(ClipboardData(text: value));
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('UPI ID copied'),
        backgroundColor: BizBiteTheme.inkDark,
      ),
    );
  }

  Widget _metaChip(IconData icon, String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 13, color: Colors.white.withValues(alpha: 0.7)),
          const SizedBox(width: 5),
          Text(
            label,
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w700,
              color: Colors.white.withValues(alpha: 0.7),
            ),
          ),
        ],
      ),
    );
  }

  // --- Staff ------------------------------------------------------------------

  /// Signed-in staff member with a role badge (admin = amber, cashier =
  /// neutral) so the till always shows who is accountable for the session.
  Widget _staffCard(BuildContext context, UserModel user) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final isAdmin = user.isAdmin;

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: BizBiteTheme.hairline),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: BizBiteTheme.brandSoft,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              user.name.isNotEmpty ? user.name[0].toUpperCase() : '?',
              style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  color: BizBiteTheme.brandDeep),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  user.name,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                      fontSize: 15, fontWeight: FontWeight.w800),
                ),
                Text(
                  user.email,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.bodySmall
                      ?.copyWith(color: scheme.onSurfaceVariant),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
            decoration: BoxDecoration(
              color: isAdmin
                  ? BizBiteTheme.brandSoft
                  : scheme.surfaceContainerHigh,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Text(
              user.role.name.toUpperCase(),
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.8,
                color: isAdmin
                    ? BizBiteTheme.brandDeep
                    : scheme.onSurfaceVariant,
              ),
            ),
          ),
        ],
      ),
    );
  }

  // --- Menu overview ------------------------------------------------------------

  Widget _menuStateCard(BuildContext context,
      {required IconData icon, required String text}) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(vertical: 28),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: BizBiteTheme.hairline),
      ),
      child: Column(
        children: [
          Icon(icon, size: 32, color: scheme.outline),
          const SizedBox(height: 8),
          Text(
            text,
            style: theme.textTheme.bodyMedium
                ?.copyWith(color: scheme.onSurfaceVariant),
          ),
        ],
      ),
    );
  }

  Widget _categoryCard(
    BuildContext context, {
    required int categoryId,
    required String name,
    required List items,
  }) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: BizBiteTheme.hairline),
      ),
      child: Theme(
        data: theme.copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          tilePadding: const EdgeInsets.symmetric(horizontal: 12),
          childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
          leading: Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: categoryFill(categoryId),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(categoryIcon(name),
                size: 19, color: categoryInk(categoryId)),
          ),
          title: Text(
            name,
            style:
                const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w700),
          ),
          subtitle: Text(
            '${items.length} item(s)',
            style: theme.textTheme.bodySmall
                ?.copyWith(color: scheme.onSurfaceVariant),
          ),
          children: [
            for (final item in items)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 4),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        item.name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                            fontSize: 13.5, fontWeight: FontWeight.w600),
                      ),
                    ),
                    Text(
                      inr(item.price),
                      style: const TextStyle(
                              fontSize: 13.5,
                              fontWeight: FontWeight.w800,
                              color: BizBiteTheme.brandDeep)
                          .merge(BizBiteTheme.numeral),
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}