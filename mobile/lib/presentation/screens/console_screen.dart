import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../features/auth/data/models/user_model.dart';
import '../../features/ops/console_controller.dart';
import '../../features/ops/data/models/console_models.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/amount.dart';

/// Owner console — the mobile slice of the Laravel admin portal:
///
///   Tables    → floor map with status moves + create/delete (admin)
///   Campaigns → percent/flat discount codes with live toggles (admin)
///   Staff     → cashier accounts with activate/deactivate (admin)
///
/// The console tile is only shown to admins; the backend additionally
/// enforces `role:admin` on every write and on GET /api/staff.
class ConsoleScreen extends StatefulWidget {
  const ConsoleScreen({super.key, required this.controller});

  final ConsoleController controller;

  @override
  State<ConsoleScreen> createState() => _ConsoleScreenState();
}

class _ConsoleScreenState extends State<ConsoleScreen> {
  @override
  void initState() {
    super.initState();
    widget.controller.load();
  }

  void _snack(String message, {bool ok = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(message),
      backgroundColor: ok ? BizBiteTheme.success : AppColors.error,
    ));
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Store Console'),
          backgroundColor: Colors.white,
          surfaceTintColor: Colors.white,
          bottom: const TabBar(
            tabs: [
              Tab(text: 'Tables'),
              Tab(text: 'Campaigns'),
              Tab(text: 'Staff'),
            ],
          ),
        ),
        body: ListenableBuilder(
          listenable: widget.controller,
          builder: (context, _) {
            final controller = widget.controller;
            if (controller.loading &&
                controller.tables.isEmpty &&
                controller.campaigns.isEmpty) {
              return const Center(child: CircularProgressIndicator());
            }
            if (controller.error.isNotEmpty && controller.tables.isEmpty) {
              return Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(controller.error,
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: AppColors.muted)),
                    const SizedBox(height: 12),
                    FilledButton(
                      onPressed: controller.load,
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              );
            }
            return TabBarView(
              children: [
                _tablesTab(controller),
                _campaignsTab(controller),
                _staffTab(controller),
              ],
            );
          },
        ),
      ),
    );
  }

  // ------------------------------------------------------------------
  // Tables
  // ------------------------------------------------------------------

  Future<void> _addTable(ConsoleController controller) async {
    final numberCtrl = TextEditingController();
    final seatsCtrl = TextEditingController(text: '4');
    final formKey = GlobalKey<FormState>();

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Add table'),
        content: Form(
          key: formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextFormField(
                controller: numberCtrl,
                maxLength: 20,
                decoration:
                    const InputDecoration(labelText: 'Table number/name'),
                validator: (v) => (v ?? '').trim().isEmpty
                    ? 'Required (max 20 chars)'
                    : null,
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: seatsCtrl,
                keyboardType: TextInputType.number,
                decoration:
                    const InputDecoration(labelText: 'Seats (default 4)'),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Back'),
          ),
          FilledButton(
            onPressed: () {
              if (formKey.currentState?.validate() ?? false) {
                Navigator.pop(context, true);
              }
            },
            child: const Text('Create'),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    final error = await controller.addTable(
      tableNumber: numberCtrl.text.trim(),
      seats: int.tryParse(seatsCtrl.text.trim()) ?? 4,
    );
    if (!mounted) return;
    error == null ? _snack('Table created.', ok: true) : _snack(error);
  }


  Future<void> _tableSheet(
      ConsoleController controller, DiningTableModel table) async {
    final action = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.all(12),
              child: Text(
                'Table ${table.tableNumber} · ${table.seats} seats',
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
            ),
            ...TableStatus.values.map(
              (status) => ListTile(
                leading: Icon(
                  switch (status) {
                    TableStatus.available => Icons.event_seat_rounded,
                    TableStatus.occupied => Icons.flatware_rounded,
                    TableStatus.reserved => Icons.bookmark_rounded,
                  },
                  color: table.status == status
                      ? AppColors.primaryDeep
                      : AppColors.slate500,
                ),
                title: Text('Mark ${status.label}'),
                trailing: table.status == status
                    ? const Icon(Icons.check, size: 18)
                    : null,
                onTap: () =>
                    Navigator.pop(context, 'status:${status.name}'),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.delete_outline_rounded,
                  color: AppColors.error),
              title: const Text('Delete table',
                  style: TextStyle(color: AppColors.error)),
              onTap: () => Navigator.pop(context, 'delete'),
            ),
          ],
        ),
      ),
    );

    if (action == null || !mounted) return;
    if (action == 'delete') {
      final error = await controller.deleteTable(table);
      if (!mounted) return;
      error == null ? _snack('Table deleted.', ok: true) : _snack(error);
      return;
    }
    final status =
        TableStatus.values.where((s) => 'status:${s.name}' == action).first;
    final error = await controller.setTableStatus(table, status);
    if (!mounted) return;
    if (error == null) {
      _snack('Table ${table.tableNumber} → ${status.label}.', ok: true);
    } else {
      _snack(error);
    }
  }


  Widget _tablesTab(ConsoleController controller) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _addTable(controller),
        icon: const Icon(Icons.add_rounded),
        label: const Text('Table'),
      ),
      body: RefreshIndicator(
        onRefresh: controller.load,
        child: controller.tables.isEmpty
            ? ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  Padding(
                    padding: EdgeInsets.symmetric(vertical: 48),
                    child: Center(
                      child: Text('No tables yet.',
                          style: TextStyle(color: AppColors.muted)),
                    ),
                  ),
                ],
              )
            : GridView.builder(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
                gridDelegate:
                    const SliverGridDelegateWithMaxCrossAxisExtent(
                  maxCrossAxisExtent: 180,
                  mainAxisSpacing: 12,
                  crossAxisSpacing: 12,
                  childAspectRatio: 1.35,
                ),
                itemCount: controller.tables.length,
                itemBuilder: (context, index) =>
                    _tableCard(controller, controller.tables[index]),
              ),
      ),
    );
  }

  Widget _tableCard(ConsoleController controller, DiningTableModel table) {
    final color = switch (table.status) {
      TableStatus.available => BizBiteTheme.success,
      TableStatus.occupied => AppColors.warningDeep,
      TableStatus.reserved => AppColors.infoCyan,
    };
    final bg = switch (table.status) {
      TableStatus.available => BizBiteTheme.successContainer,
      TableStatus.occupied => AppColors.warningBg,
      TableStatus.reserved => AppColors.surfaceMuted,
    };

    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: () => _tableSheet(controller, table),
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: BizBiteTheme.hairline),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                table.tableNumber,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  color: AppColors.ink,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                '${table.seats} seats',
                style: const TextStyle(
                  fontSize: 11,
                  color: AppColors.muted,
                ),
              ),
              const Spacer(),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: bg,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  table.status.label,
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w800,
                    color: color,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }


  // ------------------------------------------------------------------
  // Campaigns
  // ------------------------------------------------------------------

  Future<void> _addCampaign(ConsoleController controller) async {
    final nameCtrl = TextEditingController();
    final codeCtrl = TextEditingController();
    final valueCtrl = TextEditingController();
    final minCtrl = TextEditingController(text: '0');
    CampaignType type = CampaignType.percent;
    final formKey = GlobalKey<FormState>();

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('New campaign'),
          content: Form(
            key: formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextFormField(
                  controller: nameCtrl,
                  maxLength: 120,
                  decoration: const InputDecoration(labelText: 'Name'),
                  validator: (v) =>
                      (v ?? '').trim().isEmpty ? 'Required' : null,
                ),
                TextFormField(
                  controller: codeCtrl,
                  maxLength: 40,
                  textCapitalization: TextCapitalization.characters,
                  decoration: const InputDecoration(
                    labelText: 'Code (cashier types this)',
                    hintText: 'WELCOME10',
                  ),
                  validator: (v) =>
                      (v ?? '').trim().isEmpty ? 'Required' : null,
                ),
                const SizedBox(height: 8),
                SegmentedButton<CampaignType>(
                  segments: const [
                    ButtonSegment(
                        value: CampaignType.percent, label: Text('% off')),
                    ButtonSegment(
                        value: CampaignType.flat, label: Text('₹ off')),
                  ],
                  selected: {type},
                  onSelectionChanged: (selection) =>
                      setDialogState(() => type = selection.first),
                ),
                const SizedBox(height: 8),
                TextFormField(
                  controller: valueCtrl,
                  keyboardType: const TextInputType.numberWithOptions(
                      decimal: true),
                  decoration: InputDecoration(
                    labelText: type == CampaignType.percent
                        ? 'Percent (e.g. 10)'
                        : 'Flat amount (₹)',
                  ),
                  validator: (v) {
                    final value = double.tryParse((v ?? '').trim());
                    if (value == null || value <= 0) {
                      return 'Enter a valid value';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 8),
                TextFormField(
                  controller: minCtrl,
                  keyboardType: const TextInputType.numberWithOptions(
                      decimal: true),
                  decoration: const InputDecoration(
                    labelText: 'Minimum bill (₹, default 0)',
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Back'),
            ),
            FilledButton(
              onPressed: () {
                if (formKey.currentState?.validate() ?? false) {
                  Navigator.pop(context, true);
                }
              },
              child: const Text('Create'),
            ),
          ],
        ),
      ),
    );
    if (ok != true || !mounted) return;

    final error = await controller.addCampaign(
      name: nameCtrl.text.trim(),
      code: codeCtrl.text.trim().toUpperCase(),
      type: type,
      value: double.parse(valueCtrl.text.trim()),
      minOrderAmount: double.tryParse(minCtrl.text.trim()) ?? 0,
    );
    if (!mounted) return;
    error == null ? _snack('Campaign created.', ok: true) : _snack(error);
  }


  Widget _campaignsTab(ConsoleController controller) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _addCampaign(controller),
        icon: const Icon(Icons.add_rounded),
        label: const Text('Campaign'),
      ),
      body: RefreshIndicator(
        onRefresh: controller.load,
        child: controller.campaigns.isEmpty
            ? ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  Padding(
                    padding: EdgeInsets.symmetric(vertical: 48),
                    child: Center(
                      child: Text(
                        'No campaigns yet.\nCreate a percent or flat discount code.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: AppColors.muted),
                      ),
                    ),
                  ),
                ],
              )
            : ListView.separated(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
                itemCount: controller.campaigns.length,
                separatorBuilder: (_, _) => const SizedBox(height: 8),
                itemBuilder: (context, index) {
                  final campaign = controller.campaigns[index];
                  return Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(13),
                      border: Border.all(color: BizBiteTheme.hairline),
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                '${campaign.code} · ${campaign.valueLabel}',
                                style: const TextStyle(
                                  fontSize: 13.5,
                                  fontWeight: FontWeight.w800,
                                  color: AppColors.ink,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                '${campaign.name}'
                                ' · min ${inr(campaign.minOrderAmount)}',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 11.5,
                                  color: AppColors.muted,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Switch(
                          value: campaign.isActive,
                          onChanged: (_) async {
                            final error =
                                await controller.toggleCampaign(campaign);
                            if (error != null) _snack(error);
                          },
                        ),
                        IconButton(
                          icon: const Icon(Icons.delete_outline_rounded,
                              size: 20, color: AppColors.error),
                          onPressed: () async {
                            final error =
                                await controller.deleteCampaign(campaign);
                            if (error != null) _snack(error);
                          },
                        ),
                      ],
                    ),
                  );
                },
              ),
      ),
    );
  }


  // ------------------------------------------------------------------
  // Staff
  // ------------------------------------------------------------------

  Future<void> _addStaff(ConsoleController controller) async {
    final nameCtrl = TextEditingController();
    final emailCtrl = TextEditingController();
    final phoneCtrl = TextEditingController();
    final passCtrl = TextEditingController();
    final formKey = GlobalKey<FormState>();

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Add cashier'),
        content: Form(
          key: formKey,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextFormField(
                  controller: nameCtrl,
                  maxLength: 100,
                  decoration: const InputDecoration(labelText: 'Full name'),
                  validator: (v) =>
                      (v ?? '').trim().isEmpty ? 'Required' : null,
                ),
                TextFormField(
                  controller: emailCtrl,
                  maxLength: 100,
                  keyboardType: TextInputType.emailAddress,
                  decoration:
                      const InputDecoration(labelText: 'Email (login)'),
                  validator: (v) {
                    final email = (v ?? '').trim();
                    if (email.isEmpty || !email.contains('@')) {
                      return 'Enter a valid email';
                    }
                    return null;
                  },
                ),
                TextFormField(
                  controller: phoneCtrl,
                  maxLength: 20,
                  keyboardType: TextInputType.phone,
                  decoration:
                      const InputDecoration(labelText: 'Phone (optional)'),
                ),
                TextFormField(
                  controller: passCtrl,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'Password (min 6 chars)',
                  ),
                  validator: (v) => (v ?? '').length < 6
                      ? 'At least 6 characters'
                      : null,
                ),
              ],
            ),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Back'),
          ),
          FilledButton(
            onPressed: () {
              if (formKey.currentState?.validate() ?? false) {
                Navigator.pop(context, true);
              }
            },
            child: const Text('Create'),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    final error = await controller.addStaff(
      name: nameCtrl.text.trim(),
      email: emailCtrl.text.trim().toLowerCase(),
      password: passCtrl.text,
      phone: phoneCtrl.text.trim(),
    );
    if (!mounted) return;
    error == null
        ? _snack('Cashier added — they can sign in now.', ok: true)
        : _snack(error);
  }


  Widget _staffTab(ConsoleController controller) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _addStaff(controller),
        icon: const Icon(Icons.person_add_rounded),
        label: const Text('Cashier'),
      ),
      body: RefreshIndicator(
        onRefresh: controller.load,
        child: controller.staff.isEmpty
            ? ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  Padding(
                    padding: EdgeInsets.symmetric(vertical: 48),
                    child: Center(
                      child: Text(
                        'No staff visible.\n(Staff list is owner-only on the server.)',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: AppColors.muted),
                      ),
                    ),
                  ),
                ],
              )
            : ListView.separated(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
                itemCount: controller.staff.length,
                separatorBuilder: (_, _) => const SizedBox(height: 8),
                itemBuilder: (context, index) {
                  final member = controller.staff[index];
                  final lastLogin = member.lastLoginAt == null
                      ? 'never signed in'
                      : DateFormat('d MMM, HH:mm')
                          .format(member.lastLoginAt!.toLocal());
                  return Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(13),
                      border: Border.all(color: BizBiteTheme.hairline),
                    ),
                    child: Row(
                      children: [
                        Container(
                          width: 36,
                          height: 36,
                          alignment: Alignment.center,
                          decoration: const BoxDecoration(
                            gradient: AppGradients.brandMain,
                            shape: BoxShape.circle,
                          ),
                          child: Text(
                            member.name.isEmpty
                                ? '?'
                                : member.name[0].toUpperCase(),
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w800,
                              color: Colors.white,
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                member.name,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 13.5,
                                  fontWeight: FontWeight.w800,
                                  color: AppColors.ink,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                '${member.email} · $lastLogin',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                  fontSize: 11.5,
                                  color: AppColors.muted,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Column(
                          children: [
                            Text(
                              member.role == UserRole.admin
                                  ? 'Admin'
                                  : 'Cashier',
                              style: const TextStyle(
                                fontSize: 10.5,
                                fontWeight: FontWeight.w800,
                                color: AppColors.slate500,
                              ),
                            ),
                            Switch(
                              value: member.isActive,
                              onChanged: (_) async {
                                final error = await controller
                                    .setStaffActive(member, !member.isActive);
                                if (error != null) _snack(error);
                              },
                            ),
                          ],
                        ),
                      ],
                    ),
                  );
                },
              ),
      ),
    );
  }
}

