import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import '../../core/sync/offline_queued_exception.dart';
import '../../core/sync/outbox_dao.dart';
import '../orders/data/models/order_models.dart';
import 'data/models/order_queue_models.dart';
import 'data/repositories/ops_repository.dart';

/// State holder for today's kitchen/counter queue — the mobile twin of the
/// web OrderQueue board (plain ChangeNotifier like [WalletController]).
///
///   - [load] paints the queue from the network / offline cache PLUS any bill
///     this tablet took while offline, so the cashier sees their own work
///     immediately even before it syncs.
///   - [advanceStatus] walks a bill through pending → preparing → ready →
///     completed (or cancelled) via PATCH /api/orders/{id}/status. Offline it
///     is queued and the card moves immediately.
///   - [refund] / [updateDelivery] mirror the OpsController moves; both
///     return `null` on success or a cashier-ready error message. Refunds
///     require a connection (money is never queued).
class OrderQueueController extends ChangeNotifier {
  OrderQueueController({required this._repository, OutboxDao? outbox})
      : _outbox = outbox ?? OutboxDao();

  final OpsRepository _repository;
  final OutboxDao _outbox;

  /// Signed-in store — queued bills from another store stay hidden.
  int Function()? storeIdProvider;

  int get _activeStoreId => storeIdProvider?.call() ?? 0;

  List<OrderQueueOrder> orders = const [];
  bool loading = false;
  String error = '';

  /// `?status=open` filter — pending/preparing/ready only.
  bool openOnly = false;

  /// `?type=` filter — `null`/'' shows every order type.
  String? typeFilter;

  /// Set while a status/refund/delivery mutation is in flight for that
  /// order id, so the UI can disable just that card's action buttons.
  final Set<int> busyOrderIds = {};

  /// Set when the last write was queued for sync instead of reaching the
  /// server, so the screen can show a friendly confirmation.
  String? queuedNotice;

  /// A bill taken on this device that has not reached the server yet
  /// (negative id, stable across reloads).
  bool isLocalBill(OrderQueueOrder order) => order.id <= 0;

  Future<void> load() async {
    if (loading) return;
    loading = true;
    error = '';
    notifyListeners();

    try {
      final remote = await _repository.fetchQueue(
        openOnly: openOnly,
        type: (typeFilter ?? '').isEmpty ? null : typeFilter,
      );
      // Offline bills first: they are the newest and need attention.
      orders = [...await _localBills(), ...remote];
      error = '';
    } on ApiException catch (e) {
      // A cached read never throws unless there is no cache at all — in that
      // case the bills taken on this device still render.
      error = e.message;
      orders = await _localBills();
      if (e.isAuthError) rethrow;
    } catch (rawError) {
      error = rawError is ApiException
          ? rawError.message
          : 'Could not load today’s orders.';
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  /// Bills parked in `pending_orders`, rendered as read-only queue cards.
  Future<List<OrderQueueOrder>> _localBills() async {
    try {
      final bills = await _outbox.pendingBills(storeId: _activeStoreId);
      return [
        for (final bill in bills.reversed)
          OrderQueueOrder(
            id: -bill.id,
            orderNumber: '${bill.localBillNo} (queued)',
            status: OrderStatus.pending,
            orderType: OrderType.takeaway,
            paymentMode: PaymentMode.cash,
            totalAmount: bill.totalAmount,
            refundedAmount: 0,
            cashierName: 'This device',
            createdAt: DateTime.fromMillisecondsSinceEpoch(bill.createdAt),
          ),
      ];
    } catch (_) {
      // Outbox unreadable: the remote queue is still usable.
      return const [];
    }
  }

  void setFilters({bool? openOnly, String? type}) {
    this.openOnly = openOnly ?? this.openOnly;
    typeFilter = type ?? typeFilter;
    notifyListeners();
    load();
  }

  /// Advance or void a bill. Returns `null` on success (reloaded or queued
  /// offline), or the failure message for a snackbar.
  Future<String?> advanceStatus(OrderQueueOrder order, OrderStatus next) async {
    if (isLocalBill(order)) return _localBillHint;
    return _mutate(order.id, () async {
      await _repository.updateStatus(orderId: order.id, status: next.name);
      await load();
    });
  }

  /// Partial refund with a mandatory reason. Server rejects refunds that
  /// would exceed the bill total (422 surfaces here as the return value).
  /// Requires a connection — refunds are never queued.
  Future<String?> refund(
    OrderQueueOrder order, {
    required double amount,
    required String reason,
    String mode = 'cash',
  }) async {
    if (isLocalBill(order)) return _localBillHint;
    return _mutate(order.id, () async {
      await _repository.refund(
        orderId: order.id,
        amount: amount,
        reason: reason,
        mode: mode,
      );
      await load();
    });
  }

  /// Delivery workflow move (pending → assigned → out → delivered / failed).
  Future<String?> updateDelivery(
    OrderQueueOrder order, {
    required DeliveryStatus status,
    String? agent,
    String? address,
  }) async {
    if (isLocalBill(order)) return _localBillHint;
    return _mutate(order.id, () async {
      await _repository.updateDelivery(
        orderId: order.id,
        deliveryStatus: status.name,
        deliveryAgent: agent,
        deliveryAddress: address,
      );
      await load();
    });
  }

  /// Shown when the cashier taps an action on a bill that has not synced yet.
  static const String _localBillHint =
      'This bill is still syncing — it gets a real bill number once the '
      'connection is back.';

  Future<String?> _mutate(int orderId, Future<void> Function() action) async {
    busyOrderIds.add(orderId);
    queuedNotice = null;
    notifyListeners();
    try {
      await action();
      return null;
    } on OfflineQueuedException catch (queued) {
      // Durable locally + applied to the cache: success, not an error.
      queuedNotice = queued.message;
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (_) {
      return 'Something went wrong. Please try again.';
    } finally {
      busyOrderIds.remove(orderId);
      notifyListeners();
    }
  }
}
