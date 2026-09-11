import 'package:flutter/material.dart' hide MenuController;
import 'package:flutter/services.dart';

import '../../features/auth/data/models/store_profile_model.dart';
import '../../features/auth/data/models/user_model.dart';
import '../../features/auth/session_controller.dart';
import '../../features/menu/data/models/category_model.dart';
import '../../features/menu/data/models/food_item_model.dart';
import '../../features/menu/menu_controller.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/amount.dart';
import '../widgets/category_visuals.dart';

/// Screen 4 — Store console.
///
/// Layout top-to-bottom:
///   1. Store hero card — monogram avatar, name, location, phone,
///      tap-to-copy UPI VPA, currency / GSTIN / FSSAI chips
///   2. Signed-in staff card — avatar, name, email, role badge
///   3. Menu manager — search + expandable category cards with item rows.
///      Admins get add/edit/delete (FAB + row actions + bottom sheets);
///      cashiers see the same overview read-only with a lock hint.
class AdminScreen extends StatefulWidget {
  const AdminScreen({super.key, required this.session, required this.menu});

  final SessionController session;
  final MenuController menu;

  @override
  State<AdminScreen> createState() => _AdminScreenState();
}

class _AdminScreenState extends State<AdminScreen> {
  final TextEditingController _search = TextEditingController();
  String _query = '';

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  SessionController get _session => widget.session;
  MenuController get _menu => widget.menu;
  bool get _isAdmin => _session.isAdmin;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      floatingActionButton: _isAdmin
          ? FloatingActionButton.extended(
              onPressed: () => _openItemSheet(context),
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              icon: const Icon(Icons.add_rounded, size: 20),
              label: const Text('Add item',
                  style: TextStyle(fontWeight: FontWeight.w800)),
            )
          : null,
      body: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: _menu.refresh,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(12, 12, 12, 96),
          children: [
            _storeHero(context, _session.store),
            if (_session.user != null) ...[
              const SizedBox(height: 12),
              _staffCard(context, _session.user!),
            ],
            const SizedBox(height: 12),
            _menuSection(context),
          ],
        ),
      ),
    );
  }

  /// Menu manager — search + stats + expandable category cards.
  /// Writes gated by [_isAdmin] in UI and `role:admin` on the API.
  Widget _menuSection(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 4),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text('Menu manager',
                            style: theme.textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.w800)),
                        const SizedBox(width: 8),
                        _roleChip(context),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      _isAdmin
                          ? 'Add, rename and re-price — live on POS instantly.'
                          : 'Read-only preview. Sign in as admin to edit.',
                      style: theme.textTheme.bodySmall
                          ?.copyWith(color: scheme.onSurfaceVariant),
                    ),
                  ],
                ),
              ),
            ),
            if (_isAdmin)
              IconButton.filledTonal(
                tooltip: 'Add category',
                style: IconButton.styleFrom(
                  backgroundColor: AppColors.infoBg,
                  foregroundColor: AppColors.primaryDeep,
                ),
                onPressed: () => _openCategorySheet(context),
                icon: const Icon(Icons.create_new_folder_rounded, size: 19),
              ),
          ],
        ),
        const SizedBox(height: 10),
        _searchField(),
        const SizedBox(height: 10),
        ListenableBuilder(
          listenable: _menu,
          builder: (context, _) {
            if (_menu.menu == null) {
              return _menuStateCard(
                context,
                icon: _menu.loading
                    ? Icons.hourglass_top_rounded
                    : Icons.storefront_rounded,
                text: _menu.loading ? 'Loading menu…' : 'Menu unavailable',
              );
            }

            final data = _menu.menu!;
            if (data.categories.isEmpty) {
              return Column(
                children: [
                  _menuStateCard(
                    context,
                    icon: Icons.category_rounded,
                    text: 'No categories on the menu yet',
                  ),
                  if (_isAdmin) ...[
                    const SizedBox(height: 10),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        style: FilledButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                        ),
                        onPressed: () => _openCategorySheet(context),
                        icon: const Icon(Icons.add_rounded, size: 19),
                        label: const Text('Add your first category',
                            style: TextStyle(fontWeight: FontWeight.w800)),
                      ),
                    ),
                  ],
                ],
              );
            }

            return Column(
              children: [
                _statsRow(context),
                const SizedBox(height: 10),
                for (final category in data.categories)
                  _categoryCard(
                    context,
                    categoryId: category.id,
                    name: category.name,
                    items: _filteredItems(data.itemsIn(category.id)),
                  ),
              ],
            );
          },
        ),
        const SizedBox(height: 4),
        Text(
          _isAdmin
              ? 'Edits are audited (price changes tracked owner-side).'
              : 'Full menu & store editing lives in the web console.',
          style: theme.textTheme.bodySmall?.copyWith(color: scheme.outline),
        ),
      ],
    );
  }

  Widget _searchField() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: BizBiteTheme.hairline),
        boxShadow: AppShadows.card,
      ),
      child: TextField(
        controller: _search,
        onChanged: (v) => setState(() => _query = v.trim().toLowerCase()),
        textInputAction: TextInputAction.search,
        decoration: InputDecoration(
          prefixIcon: const Icon(Icons.search_rounded, size: 20),
          suffixIcon: _query.isEmpty
              ? null
              : IconButton(
                  tooltip: 'Clear search',
                  icon: const Icon(Icons.close_rounded, size: 18),
                  onPressed: () {
                    _search.clear();
                    setState(() => _query = '');
                  },
                ),
          hintText: 'Search items…',
          border: InputBorder.none,
          enabledBorder: InputBorder.none,
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppRadius.lg),
            borderSide:
                const BorderSide(color: AppColors.primary, width: 1.5),
          ),
        ),
      ),
    );
  }

  Widget _roleChip(BuildContext context) {
    final admin = _isAdmin;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: admin ? const Color(0xFFFFF3D6) : AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(AppRadius.full),
        border: Border.all(
          color: admin ? const Color(0xFFB7791F) : BizBiteTheme.hairline,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(admin ? Icons.lock_open_rounded : Icons.lock_rounded,
              size: 11,
              color: admin
                  ? const Color(0xFFB7791F)
                  : Theme.of(context).colorScheme.onSurfaceVariant),
          const SizedBox(width: 4),
          Text(admin ? 'ADMIN' : 'VIEW ONLY',
              style:
                  const TextStyle(fontSize: 10, fontWeight: FontWeight.w800)),
        ],
      ),
    );
  }

  Widget _statsRow(BuildContext context) {
    final data = _menu.menu!;
    final items = data.items;
    double avg = 0;
    if (items.isNotEmpty) {
      double sum = 0;
      for (final item in items) {
        sum += item.price;
      }
      avg = sum / items.length;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: BizBiteTheme.hairline),
        boxShadow: AppShadows.card,
      ),
      child: Row(
        children: [
          _stat(context, '${data.categories.length}', 'Categories'),
          _statDivider(),
          _stat(context, '${items.length}', 'Items'),
          _statDivider(),
          _stat(context, inr(avg), 'Avg price'),
        ],
      ),
    );
  }

  Widget _stat(BuildContext context, String value, String label) {
    return Expanded(
      child: Column(
        children: [
          Text(value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800)
                  .merge(BizBiteTheme.numeral)),
          Text(label,
              style: Theme.of(context)
                  .textTheme
                  .bodySmall
                  ?.copyWith(color: AppColors.muted)),
        ],
      ),
    );
  }

  Widget _statDivider() {
    return Container(width: 1, height: 28, color: BizBiteTheme.hairline);
  }

  List _filteredItems(List items) {
    if (_query.isEmpty) return items;
    return items
        .where((item) =>
            (item.name as String).toLowerCase().contains(_query))
        .toList();
  }

  void _snack(BuildContext context, String message, {bool error = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor:
            error ? Theme.of(context).colorScheme.error : AppColors.ink,
        behavior: SnackBarBehavior.floating,
      ),
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
        boxShadow: AppShadows.card,
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
          trailing: _isAdmin
              ? _categoryMenu(context,
                  categoryId: categoryId, name: name)
              : null,
          children: [
            if (items.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Text(
                  _query.isEmpty
                      ? 'No items in this category yet.'
                      : 'No items match "$_query".',
                  style: theme.textTheme.bodySmall
                      ?.copyWith(color: scheme.onSurfaceVariant),
                ),
              ),
            for (final item in items) _itemRow(context, item),
            if (_isAdmin)
              Align(
                alignment: Alignment.centerRight,
                child: TextButton.icon(
                  onPressed: () =>
                      _openItemSheet(context, categoryId: categoryId),
                  icon: const Icon(Icons.add_rounded, size: 17),
                  label: const Text('Add item here'),
                  style: TextButton.styleFrom(
                    foregroundColor: AppColors.primaryDeep,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _itemRow(BuildContext context, FoodItemModel item) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        children: [
          Expanded(
            child: Text(
              item.name as String,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style:
                  const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600),
            ),
          ),
          Text(
            inr(item.price as num),
            style: const TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w800,
                    color: BizBiteTheme.brandDeep)
                .merge(BizBiteTheme.numeral),
          ),
          if (_isAdmin) ...[
            IconButton(
              tooltip: 'Edit item',
              visualDensity: VisualDensity.compact,
              iconSize: 18,
              onPressed: () => _openItemSheet(context, editItem: item),
              icon: const Icon(Icons.edit_rounded, color: AppColors.slate500),
            ),
            IconButton(
              tooltip: 'Delete item',
              visualDensity: VisualDensity.compact,
              iconSize: 18,
              onPressed: () => _confirmDelete(context, item),
              icon: const Icon(Icons.delete_outline_rounded,
                  color: AppColors.error),
            ),
          ],
        ],
      ),
    );
  }

  Widget _categoryMenu(BuildContext context,
      {required int categoryId, required String name}) {
    return PopupMenuButton<String>(
      tooltip: 'Category actions',
      icon: const Icon(Icons.more_vert_rounded, size: 19),
      onSelected: (value) {
        if (value == 'rename') {
          _openCategorySheet(context,
              categoryId: categoryId, initialName: name);
        } else if (value == 'add') {
          _openItemSheet(context, categoryId: categoryId);
        }
      },
      itemBuilder: (context) => const [
        PopupMenuItem(
          value: 'rename',
          child: Row(
            children: [
              Icon(Icons.drive_file_rename_outline_rounded, size: 17),
              SizedBox(width: 8),
              Text('Rename category'),
            ],
          ),
        ),
        PopupMenuItem(
          value: 'add',
          child: Row(
            children: [
              Icon(Icons.add_rounded, size: 17),
              SizedBox(width: 8),
              Text('Add item here'),
            ],
          ),
        ),
      ],
    );
  }