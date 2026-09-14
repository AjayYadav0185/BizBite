import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../features/ops/data/models/order_queue_models.dart';
import '../../features/ops/order_queue_controller.dart';
import '../../features/orders/data/models/order_models.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/amount.dart';

/// Today's kitchen/counter queue — the mobile twin of the web OrderQueue
/// board. Filter chips (open-only + order-type split), status advance
/// buttons per bill, refund and delivery moves via dialogs/sheets.
class OrderQueueScreen extends StatefulWidget {
  const OrderQueueScreen({super.key, required this.controller});

  final OrderQueueController controller;

  @override
  State<OrderQueueScreen> createState() => _OrderQueueScreenState();
}

class _OrderQueueScreenState extends State<OrderQueueScreen> {
  static const List<(String?, String)> _typeChoices = [
    (null, 'All'),
    ('dine_in', 'Dine-in'),
    ('takeaway', 'Takeaway'),
    ('parcel', 'Parcel'),
    ('delivery', 'Delivery'),
  ];

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

  // ------------------------------------------------------------------
  // Actions
  // ------------------------------------------------------------------

  Future<void> _advance(OrderQueueOrder order) async {
    final next = switch (order.status) {
      OrderStatus.pending => OrderStatus.preparing,
      OrderStatus.preparing => OrderStatus.ready,
      OrderStatus.ready => OrderStatus.completed,
      _ => null,
    };
    if (next == null) return;
    final error = await widget.controller.advanceStatus(order, next);
    if (error != null) _snack(error);
  }

  Future<void> _cancel(OrderQueueOrder order) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancel bill?'),
        content: Text('Bill ${order.orderNumber} for ${inr(order.totalAmount)} '
            'will be voided. This cannot be undone.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Keep bill'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Cancel bill'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    final error =
        await widget.controller.advanceStatus(order, OrderStatus.cancelled);
    if (error != null) _snack(error);
  }

  Future<void> _refund(OrderQueueOrder order) async {
    final amountCtrl = TextEditingController();
    final reasonCtrl = TextEditingController();
    final formKey = GlobalKey<FormState>();

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Refund ${order.orderNumber}'),
        content: Form(
          key: formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'Billed ${inr(order.totalAmount)}'
                '${order.hasRefund ? ' · refunded ${inr(order.refundedAmount)}' : ''}',
                style:
                    const TextStyle(color: AppColors.muted, fontSize: 12.5),
              ),
              const SizedBox(height: 14),
              TextFormField(
                controller: amountCtrl,
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(
                  labelText: 'Refund amount (₹)',
                  prefixText: '₹ ',
                ),
                validator: (v) {
                  final amount = double.tryParse((v ?? '').trim());
                  if (amount == null || amount <= 0) {
                    return 'Enter a valid amount';
                  }
                  if (amount > order.totalAmount) {
                    return 'Cannot exceed the bill total';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: reasonCtrl,
                maxLines: 2,
                maxLength: 200,
                decoration: const InputDecoration(
                  labelText: 'Reason (required)',
                  hintText: 'e.g. wrong item delivered',
                ),
                validator: (v) =>
                    (v ?? '').trim().isEmpty ? 'A reason is required' : null,
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
            child: const Text('Issue refund'),
          ),
        ],
      ),
    );

    if (ok != true || !mounted) return;
    final error = await widget.controller.refund(
      order,
      amount: double.parse(amountCtrl.text.trim()),
      reason: reasonCtrl.text.trim(),
    );
    if (!mounted) return;
    if (error != null) {
      _snack(error);
    } else {
      _snack('Refund issued for ${order.orderNumber}.', ok: true);
    }
  }

  Future<void> _deliveryMove(OrderQueueOrder order) async {
    final selected = await showModalBottomSheet<DeliveryStatus>(
      context: context,
      showDragHandle: true,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Padding(
              padding: EdgeInsets.all(12),
              child: Text('Delivery move',
                  style: TextStyle(fontWeight: FontWeight.w800)),
            ),
            ...DeliveryStatus.values.map(
              (status) => ListTile(
                leading: Icon(
                  switch (status) {
                    DeliveryStatus.pending => Icons.schedule_rounded,
                    DeliveryStatus.assigned => Icons.person_search_rounded,
                    DeliveryStatus.out => Icons.delivery_dining_rounded,
                    DeliveryStatus.delivered => Icons.check_circle_rounded,
                    DeliveryStatus.failed => Icons.error_outline_rounded,
                  },
                  color: status == DeliveryStatus.delivered
                      ? BizBiteTheme.success
                      : status == DeliveryStatus.failed
                          ? AppColors.error
                          : AppColors.slate500,
                ),
                title: Text(status.label),
                trailing: order.deliveryStatus == status
                    ? const Icon(Icons.check, size: 18)
                    : null,
                onTap: () => Navigator.pop(context, status),
              ),
            ),
          ],
        ),
      ),
    );
    if (selected == null || selected == order.deliveryStatus) return;
    final error =
        await widget.controller.updateDelivery(order, status: selected);
    if (error != null) _snack(error);
  }


  // ------------------------------------------------------------------
  // Build
  // ------------------------------------------------------------------

  @override
  Widget build(BuildContext context) {
    final controller = widget.controller;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Today’s Orders'),
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
      ),
      body: ListenableBuilder(
        listenable: controller,
        builder: (context, _) {
          if (controller.loading && controller.orders.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }
          if (controller.error.isNotEmpty && controller.orders.isEmpty) {
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
          return Column(
            children: [
              _filters(controller),
              Expanded(
                child: RefreshIndicator(
                  onRefresh: controller.load,
                  child: controller.orders.isEmpty
                      ? ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: const [
                            Padding(
                              padding: EdgeInsets.symmetric(vertical: 64),
                              child: Center(
                                child: Text(
                                  'No bills match these filters yet.\n'
                                  'New bills appear here as the till settles them.',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(color: AppColors.muted),
                                ),
                              ),
                            ),
                          ],
                        )
                      : ListView.separated(
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                          itemCount: controller.orders.length,
                          separatorBuilder: (_, _) =>
                              const SizedBox(height: 10),
                          itemBuilder: (context, index) => _OrderCard(
                            order: controller.orders[index],
                            controller: controller,
                            onAdvance: () => _advance(controller.orders[index]),
                            onCancel: () => _cancel(controller.orders[index]),
                            onRefund: () => _refund(controller.orders[index]),
                            onDelivery: () =>
                                _deliveryMove(controller.orders[index]),
                          ),
                        ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _filters(OrderQueueController controller) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: BizBiteTheme.hairline)),
      ),
      child: Wrap(
        spacing: 8,
        runSpacing: 8,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          FilterChip(
            selected: controller.openOnly,
            label: const Text('Open only'),
            onSelected: (v) => controller.setFilters(openOnly: v),
          ),
          ..._typeChoices.map(
            (choice) => ChoiceChip(
              label: Text(choice.$2),
              selected: (controller.typeFilter ?? '') == (choice.$1 ?? ''),
              onSelected: (_) => controller.setFilters(type: choice.$1),
            ),
          ),
        ],
      ),
    );
  }
}


class _OrderCard extends StatelessWidget {
  const _OrderCard({
    required this.order,
    required this.controller,
    required this.onAdvance,
    required this.onCancel,
    required this.onRefund,
    required this.onDelivery,
  });

  final OrderQueueOrder order;
  final OrderQueueController controller;
  final VoidCallback onAdvance;
  final VoidCallback onCancel;
  final VoidCallback onRefund;
  final VoidCallback onDelivery;

  bool get _busy => controller.busyOrderIds.contains(order.id);

  Color get _statusColor => switch (order.status) {
        OrderStatus.pending => AppColors.warningDeep,
        OrderStatus.preparing => AppColors.infoCyan,
        OrderStatus.ready => BizBiteTheme.success,
        OrderStatus.completed => AppColors.slate500,
        OrderStatus.cancelled => AppColors.error,
      };

  Color get _statusBg => switch (order.status) {
        OrderStatus.pending => AppColors.warningBg,
        OrderStatus.ready => BizBiteTheme.successContainer,
        OrderStatus.cancelled => AppColors.errorBg,
        _ => AppColors.surfaceMuted,
      };

  @override
  Widget build(BuildContext context) {
    final time = order.createdAt == null
        ? ''
        : DateFormat('HH:mm').format(order.createdAt!.toLocal());

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: BizBiteTheme.hairline),
      ),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  '${order.orderNumber.isNotEmpty ? order.orderNumber : '#${order.id}'}'
                  '${order.tableNumber.isNotEmpty ? ' · T${order.tableNumber}' : ''}',
                  style: const TextStyle(
                    fontSize: 14.5,
                    fontWeight: FontWeight.w800,
                    color: AppColors.ink,
                  ),
                ),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                decoration: BoxDecoration(
                  color: _statusBg,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  order.status.label,
                  style: TextStyle(
                    fontSize: 10.5,
                    fontWeight: FontWeight.w800,
                    color: _statusColor,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            '$time · ${order.orderType.label} · ${order.paymentMode.label}'
            '${order.cashierName.isNotEmpty ? ' · ${order.cashierName}' : ''}',
            style: const TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w500,
              color: AppColors.muted,
            ),
          ),
          const SizedBox(height: 8),
          ...order.items.map(
            (item) => Padding(
              padding: const EdgeInsets.only(bottom: 2),
              child: Row(
                children: [
                  Text(
                    '${item.quantity} ×',
                    style: const TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w700,
                      color: AppColors.slate600,
                    ),
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      item.foodItemName,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontSize: 12.5, color: AppColors.ink),
                    ),
                  ),
                  Text(
                    inr(item.subtotal),
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppColors.muted,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const Divider(height: 18, color: BizBiteTheme.hairline),
          Row(
            children: [
              if (order.hasRefund)
                Container(
                  margin: const EdgeInsets.only(right: 8),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppColors.errorBg,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    'Refunded ${inr(order.refundedAmount)}',
                    style: const TextStyle(
                      fontSize: 10.5,
                      fontWeight: FontWeight.w800,
                      color: AppColors.errorDeep,
                    ),
                  ),
                ),
              const Spacer(),
              Text(
                inr(order.totalAmount),
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  color: AppColors.primaryDeep,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          _actions(),
        ],
      ),
    );
  }


  Widget _actions() {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        if (order.status != OrderStatus.completed &&
            order.status != OrderStatus.cancelled)
          FilledButton.icon(
            onPressed: _busy ? null : onAdvance,
            icon: Icon(
              switch (order.status) {
                OrderStatus.pending => Icons.soup_kitchen_rounded,
                OrderStatus.preparing => Icons.done_all_rounded,
                _ => Icons.verified_rounded,
              },
              size: 16,
            ),
            label: Text(switch (order.status) {
              OrderStatus.pending => 'Start preparing',
              OrderStatus.preparing => 'Mark ready',
              _ => 'Complete',
            }),
            style: FilledButton.styleFrom(
              backgroundColor: BizBiteTheme.brand,
              padding: const EdgeInsets.symmetric(horizontal: 14),
            ),
          ),
        if (order.orderType == OrderType.delivery &&
            order.status != OrderStatus.cancelled &&
            order.status != OrderStatus.completed)
          OutlinedButton.icon(
            onPressed: _busy ? null : onDelivery,
            icon: const Icon(Icons.delivery_dining_rounded, size: 16),
            label: Text(order.deliveryStatus?.label ?? 'Delivery'),
          ),
        OutlinedButton.icon(
          onPressed: _busy ? null : onRefund,
          icon: const Icon(Icons.replay_rounded, size: 16),
          label: const Text('Refund'),
        ),
        if (order.status.isOpen)
          TextButton.icon(
            onPressed: _busy ? null : onCancel,
            style: TextButton.styleFrom(foregroundColor: AppColors.error),
            icon: const Icon(Icons.block_rounded, size: 16),
            label: const Text('Cancel'),
          ),
      ],
    );
  }
}

