import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import 'data/models/order_queue_models.dart';
import 'data/repositories/ops_repository.dart';

/// State holder for today's kitchen/counter queue — the mobile twin of the
/// web OrderQueue board (plain ChangeNotifier like [WalletController]).
///
///   - [load] paints the queue from GET /api/orders with the active filters.
///   - [advanceStatus] walks a bill through pending → preparing → ready →
///     completed (or cancelled) via PATCH /api/orders/{id}/status.
///   - [refund] / [updateDelivery] mirror the OpsController moves; both
///     return `null` on success or a cashier-ready error message.
class OrderQueueController extends ChangeNotifier {
  OrderQueueController({required this._repository});

  final OpsRepository _repository;

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

  Future<void> load() async {
    if (loading) return;
    loading = true;
    error = '';
    notifyListeners();

    try {
      orders = await _repository.fetchQueue(
        openOnly: openOnly,
        type: (typeFilter ?? '').isEmpty ? null : typeFilter,
      );
      error = '';
    } on ApiException catch (e) {
      error = e.message;
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

  void setFilters({bool? openOnly, String? type}) {
    this.openOnly = openOnly ?? this.openOnly;
    typeFilter = type ?? typeFilter;
    notifyListeners();
    load();
  }

  /// Advance or void a bill. Returns `null` on success (queue reloaded),
  /// or the failure message to show in a snackbar.
  Future<String?> advanceStatus(OrderQueueOrder order, OrderStatus next) async {
    return _mutate(order.id, () async {
      await _repository.updateStatus(orderId: order.id, status: next.name);
      await load();
    });
  }

  /// Partial refund with a mandatory reason. Server rejects refunds that
  /// would exceed the bill total (422 surfaces here as the return value).
  Future<String?> refund(
    OrderQueueOrder order, {
    required double amount,
    required String reason,
    String mode = 'cash',
  }) async {
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

  Future<String?> _mutate(int orderId, Future<void> Function() action) async {
    busyOrderIds.add(orderId);
    notifyListeners();
    try {
      await action();
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
