import 'package:dio/dio.dart';

import '../../../../core/config/api_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/sync/cache_keys.dart';
import '../../../../core/sync/offline_gateway.dart';
import '../../../../core/sync/offline_queued_exception.dart';
import '../../../../core/sync/offline_sources.dart';
import '../../../../core/utils/parse_utils.dart';
import '../models/console_models.dart';
import '../models/order_queue_models.dart';
import '../models/report_models.dart';
import '../models/shift_models.dart';

/// Offline-first typed gateway for every ops endpoint in `routes/api.php`
/// that is NOT already owned by the POS billing flow:
///
///   Order queue   GET    /orders (+ ?status=open&type=…)
///                 PATCH  /orders/{id}/status     (queued offline)
///                 POST   /orders/{id}/refund     (ONLINE ONLY — money)
///                 PATCH  /orders/{id}/delivery   (queued offline)
///   Shifts        GET    /shifts · POST /shifts/open · POST /shifts/{id}/close
///   Reports       GET    /reports/hourly · /reports/best-sellers · /reports/range
///   Tables        GET    /tables · POST · PATCH/{id} · DELETE/{id}   (admin writes)
///   Campaigns     GET    /campaigns · POST · PATCH/{id} · DELETE/{id} (admin writes)
///   Staff         GET    /staff · POST · PATCH/{id}                  (admin only)
///
/// OFFLINE POLICY
///   reads   → network-first, SQLite fallback (`cache_entries`); the screen
///             keeps rendering the last-good envelope and flags itself as
///             "cached" through [OfflineGateway].
///   writes  → when the link is dead the request is parked in
///             `pending_mutations` (idempotent verbs) and the change is
///             applied to the cache immediately, so the UI stays truthful.
///   refunds → NEVER queued. `OpsController::refund` writes a ledger row and a
///             negative payment leg; replaying it after a timeout the server
///             actually processed would double-debit. The cashier is told to
///             reconnect instead.
class OpsRepository {
  OpsRepository({required this._client, OfflineGateway? gateway})
      : _gateway = gateway ?? OfflineGateway();

  final DioClient _client;
  final OfflineGateway _gateway;

  // ------------------------------------------------------------------
  // Order queue
  // ------------------------------------------------------------------

  /// GET /api/orders — today's bills for the caller's store, oldest first.
  /// [openOnly] maps to `?status=open` (pending/preparing/ready only);
  /// [type] maps to `?type=dine_in|takeaway|parcel|delivery`.
  ///
  /// Each filter combination gets its own cache key so switching filters
  /// offline never mixes two result sets.
  Future<List<OrderQueueOrder>> fetchQueue({
    bool openOnly = false,
    String? type,
  }) async {
    final cleanType = (type ?? '').trim();
    final read = await _gateway.readRaw(
      key: CacheKeys.queue(openOnly: openOnly, type: cleanType),
      source: OfflineSources.queue,
      fetch: () async {
        try {
          final response = await _client.get<dynamic>(
            ApiConfig.orders,
            query: {
              if (openOnly) 'status': 'open',
              if (cleanType.isNotEmpty) 'type': cleanType,
            },
          );
          return response.data;
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );
    return toListOfMaps(toMap(read.data)['orders'])
        .map(OrderQueueOrder.fromJson)
        .toList(growable: false);
  }

  /// PATCH /api/orders/{order}/status — advance or void a bill.
  ///
  /// Offline: queued (a status transition is idempotent) and the cached queue
  /// rows move immediately; throws [OfflineQueuedException], which callers
  /// treat as SUCCESS.
  Future<OrderQueueOrder> updateStatus({
    required int orderId,
    required String status,
  }) async {
    final path = ApiConfig.orderStatus(orderId);
    final body = <String, dynamic>{'status': status};

    Map<String, dynamic>? updated;
    final queued = await _gateway.queueWhenOffline(
      method: 'PATCH',
      path: path,
      data: body,
      label: 'Order status',
      send: () async {
        try {
          final response = await _client.patch<dynamic>(path, data: body);
          updated = toMap(toMap(response.data)['order']);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _gateway.cache.patchWhere(
        prefix: CacheKeys.queuePrefix,
        listKey: CacheKeys.ordersListKey,
        match: (row) => toInt(row['id']) == orderId,
        patch: body,
      );
      throw OfflineQueuedException(label: 'Order status');
    }
    return OrderQueueOrder.fromJson(updated ?? const {});
  }

  /// POST /api/orders/{order}/refund — partial refund with reason
  /// (ledger + negative payment leg + audit row, server-validated).
  ///
  /// ONLINE ONLY: money can never be promised by a device that cannot reach
  /// the ledger. Offline callers get a network [ApiException] explaining it.
  Future<OrderQueueOrder> refund({
    required int orderId,
    required double amount,
    required String reason,
    String mode = 'cash',
  }) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.orderRefund(orderId),
        data: {
          'amount': amount.toStringAsFixed(2),
          'reason': reason,
          'mode': mode,
        },
      );
      return OrderQueueOrder.fromJson(toMap(toMap(response.data)['order']));
    } on DioException catch (error) {
      final exception = apiExceptionFrom(error);
      if (exception.isNetworkError) {
        throw ApiException(
          message: 'Refunds need a connection — reconnect and try again.',
          type: ApiExceptionType.network,
          original: error,
        );
      }
      throw exception;
    }
  }

  /// PATCH /api/orders/{order}/delivery — delivery workflow move (queued
  /// offline; each move is idempotent).
  Future<OrderQueueOrder> updateDelivery({
    required int orderId,
    required String deliveryStatus,
    String? deliveryAgent,
    String? deliveryAddress,
  }) async {
    final path = ApiConfig.orderDelivery(orderId);
    final body = <String, dynamic>{
      'delivery_status': deliveryStatus,
      if (deliveryAgent != null && deliveryAgent.trim().isNotEmpty)
        'delivery_agent': deliveryAgent.trim(),
      if (deliveryAddress != null && deliveryAddress.trim().isNotEmpty)
        'delivery_address': deliveryAddress.trim(),
    };

    Map<String, dynamic>? updated;
    final queued = await _gateway.queueWhenOffline(
      method: 'PATCH',
      path: path,
      data: body,
      label: 'Delivery update',
      send: () async {
        try {
          final response = await _client.patch<dynamic>(path, data: body);
          updated = toMap(toMap(response.data)['order']);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _gateway.cache.patchWhere(
        prefix: CacheKeys.queuePrefix,
        listKey: CacheKeys.ordersListKey,
        match: (row) => toInt(row['id']) == orderId,
        patch: body,
      );
      throw OfflineQueuedException(label: 'Delivery update');
    }
    return OrderQueueOrder.fromJson(updated ?? const {});
  }

  // ------------------------------------------------------------------
  // Shifts (cash drawer)
  // ------------------------------------------------------------------

  /// GET /api/shifts — latest 30 sessions, newest `opened_at` first.
  Future<List<Shift>> fetchShifts() async {
    final read = await _gateway.readRaw(
      key: CacheKeys.shifts,
      source: OfflineSources.shifts,
      fetch: () async {
        try {
          final response = await _client.get<dynamic>(ApiConfig.shifts);
          return response.data;
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );
    return toListOfMaps(toMap(read.data)['shifts'])
        .map(Shift.fromJson)
        .toList(growable: false);
  }

  /// POST /api/shifts/open — start a drawer session (201 Created).
  ///
  /// Offline: queued and a local placeholder row is appended to the cached
  /// list so the drawer banner shows an open session immediately. The replay
  /// runs before the next pull, so the server row replaces the placeholder.
  Future<Shift> openShift({double openingCash = 0, String? notes}) async {
    final body = <String, dynamic>{
      'opening_cash': openingCash.toStringAsFixed(2),
      if (notes != null && notes.trim().isNotEmpty) 'notes': notes.trim(),
    };

    Map<String, dynamic>? created;
    final queued = await _gateway.queueWhenOffline(
      method: 'POST',
      path: ApiConfig.shiftOpen,
      data: body,
      label: 'Shift open',
      send: () async {
        try {
          final response = await _client.post<dynamic>(
            ApiConfig.shiftOpen,
            data: body,
          );
          created = toMap(toMap(response.data)['shift']);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _gateway.cache.upsertWhere(
        prefix: CacheKeys.shifts,
        listKey: CacheKeys.shiftsListKey,
        match: (row) => false, // always append a fresh local session
        row: <String, dynamic>{
          'id': -DateTime.now().millisecondsSinceEpoch,
          'status': 'open',
          'opened_at': DateTime.now().toIso8601String(),
          'closed_at': null,
          'opening_cash': openingCash.toStringAsFixed(2),
          'closing_cash': '0.00',
          'expected_cash': '0.00',
          'notes': notes ?? '',
        },
      );
      throw OfflineQueuedException(label: 'Shift open');
    }
    return Shift.fromJson(created ?? const {});
  }

  /// POST /api/shifts/{shift}/close — count the drawer; the server computes
  /// `expected_cash` and returns the variance in the same response.
  ///
  /// Offline: queued and the cached row is marked closed with the counted
  /// cash; the variance is unknown until the replay, so it reports `0`.
  Future<ShiftCloseResult> closeShift({
    required int shiftId,
    double closingCash = 0,
  }) async {
    final path = ApiConfig.shiftClose(shiftId);
    final body = <String, dynamic>{
      'closing_cash': closingCash.toStringAsFixed(2),
    };

    Map<String, dynamic>? payload;
    final queued = await _gateway.queueWhenOffline(
      method: 'POST',
      path: path,
      data: body,
      label: 'Shift close',
      send: () async {
        try {
          final response = await _client.post<dynamic>(path, data: body);
          payload = toMap(response.data);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _gateway.cache.patchWhere(
        prefix: CacheKeys.shifts,
        listKey: CacheKeys.shiftsListKey,
        match: (row) => toInt(row['id']) == shiftId,
        patch: {
          'status': 'closed',
          'closed_at': DateTime.now().toIso8601String(),
          'closing_cash': closingCash.toStringAsFixed(2),
        },
      );
      throw OfflineQueuedException(label: 'Shift close');
    }
    return ShiftCloseResult.fromJson(payload ?? const {});
  }

  // ------------------------------------------------------------------
  // Reports (read-only — cached per date / range)
  // ------------------------------------------------------------------

  /// GET /api/reports/hourly — 24 zero-filled buckets for one day (Y-m-d).
  Future<ReportHourlyBundle> hourly({required String date}) async {
    final read = await _gateway.readRaw(
      key: CacheKeys.hourly(date),
      source: OfflineSources.reports,
      fetch: () async {
        try {
          final response = await _client.get<dynamic>(
            ApiConfig.reportsHourly,
            query: {'date': date},
          );
          return response.data;
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );
    final map = toMap(read.data);
    return ReportHourlyBundle(
      date: toNullableString(map['date'], fallback: date),
      rows: toListOfMaps(map['hourly'])
          .map(HourlyRow.fromJson)
          .toList(growable: false),
    );
  }

  /// GET /api/reports/best-sellers — items ranked by quantity in `[from, to]`.
  Future<List<BestSeller>> bestSellers({
    required String from,
    required String to,
  }) async {
    final read = await _gateway.readRaw(
      key: CacheKeys.bestSellers(from, to),
      source: OfflineSources.reports,
      fetch: () async {
        try {
          final response = await _client.get<dynamic>(
            ApiConfig.reportsBestSellers,
            query: {'from': from, 'to': to},
          );
          return response.data;
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );
    return toListOfMaps(toMap(read.data)['best_sellers'])
        .map(BestSeller.fromJson)
        .toList(growable: false);
  }

  /// GET /api/reports/range — KPI rollup for a date range.
  Future<RangeReport> range({required String from, required String to}) async {
    final read = await _gateway.readRaw(
      key: CacheKeys.range(from, to),
      source: OfflineSources.reports,
      fetch: () async {
        try {
          final response = await _client.get<dynamic>(
            ApiConfig.reportsRange,
            query: {'from': from, 'to': to},
          );
          return response.data;
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );
    return RangeReport.fromJson(toMap(read.data));
  }

  // ------------------------------------------------------------------
  // Tables (reads for all staff, admin-only writes)
  // ------------------------------------------------------------------

  /// GET /api/tables — ordered by table_number.
  Future<List<DiningTableModel>> fetchTables() async {
    final read = await _gateway.readRaw(
      key: CacheKeys.tables,
      source: OfflineSources.tables,
      fetch: () async {
        try {
          final response = await _client.get<dynamic>(ApiConfig.tables);
          return response.data;
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );
    return _parseTables(read.data);
  }

  List<DiningTableModel> _parseTables(Object? raw) =>
      toListOfMaps(toMap(raw)['tables'])
          .map(DiningTableModel.fromJson)
          .toList(growable: false);

  /// POST /api/tables — create a table (admin only, 201 Created).
  ///
  /// Offline: queued and a placeholder row (negative id) is appended to the
  /// cached floor plan. The replay happens before the next pull, and
  /// `table_number` is unique server-side, so a replay after a timeout the
  /// server already processed comes back as a parked `failed` row instead of
  /// creating a duplicate table.
  Future<DiningTableModel> storeTable({
    required String tableNumber,
    int seats = 4,
  }) async {
    final body = <String, dynamic>{'table_number': tableNumber, 'seats': seats};

    Map<String, dynamic>? created;
    final queued = await _gateway.queueWhenOffline(
      method: 'POST',
      path: ApiConfig.tables,
      data: body,
      label: 'New table $tableNumber',
      send: () async {
        try {
          final response = await _client.post<dynamic>(
            ApiConfig.tables,
            data: body,
          );
          created = toMap(toMap(response.data)['table']);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      final localRow = <String, dynamic>{
        'id': -DateTime.now().millisecondsSinceEpoch,
        'table_number': tableNumber,
        'seats': seats,
        'status': TableStatus.available.name,
        'current_order_id': null,
      };
      await _gateway.cache.upsertWhere(
        prefix: CacheKeys.tables,
        listKey: CacheKeys.tablesListKey,
        match: (row) => false, // always append a fresh local table
        row: localRow,
      );
      throw OfflineQueuedException(label: 'New table');
    }
    return DiningTableModel.fromJson(created ?? const {});
  }

  /// PATCH /api/tables/{table} — status / seats update (admin only).
  /// `status: available` also clears `current_order_id` server-side.
  Future<DiningTableModel> updateTable({
    required int tableId,
    String? status,
    int? seats,
  }) async {
    final path = ApiConfig.table(tableId);
    final body = <String, dynamic>{'status': ?status, 'seats': ?seats};

    Map<String, dynamic>? updated;
    final queued = await _gateway.queueWhenOffline(
      method: 'PATCH',
      path: path,
      data: body,
      label: 'Table update',
      send: () async {
        try {
          final response = await _client.patch<dynamic>(path, data: body);
          updated = toMap(toMap(response.data)['table']);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _gateway.cache.patchWhere(
        prefix: CacheKeys.tables,
        listKey: CacheKeys.tablesListKey,
        match: (row) => toInt(row['id']) == tableId,
        patch: {
          ...body,
          if (status == TableStatus.available.name) 'current_order_id': null,
        },
      );
      throw OfflineQueuedException(label: 'Table update');
    }
    return DiningTableModel.fromJson(updated ?? const {});
  }

  /// DELETE /api/tables/{table} — remove a table (admin only).
  Future<void> destroyTable({required int tableId}) async {
    final path = ApiConfig.table(tableId);
    final queued = await _gateway.queueWhenOffline(
      method: 'DELETE',
      path: path,
      data: null,
      label: 'Table deleted',
      send: () async {
        try {
          await _client.delete<dynamic>(path);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _gateway.cache.patchWhere(
        prefix: CacheKeys.tables,
        listKey: CacheKeys.tablesListKey,
        match: (row) => toInt(row['id']) == tableId,
        remove: true,
      );
      throw OfflineQueuedException(label: 'Table delete');
    }
  }

  // ------------------------------------------------------------------
  // Campaigns (admin-only writes)
  // ------------------------------------------------------------------

  /// GET /api/campaigns — latest 100, newest first.
  Future<List<CampaignModel>> fetchCampaigns() async {
    final read = await _gateway.readRaw(
      key: CacheKeys.campaigns,
      source: OfflineSources.campaigns,
      fetch: () async {
        try {
          final response = await _client.get<dynamic>(ApiConfig.campaigns);
          return response.data;
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );
    return toListOfMaps(toMap(read.data)['campaigns'])
        .map(CampaignModel.fromJson)
        .toList(growable: false);
  }

  /// POST /api/campaigns — create a percent/flat discount code (admin only).
  ///
  /// Offline: queued and a placeholder row (negative id) is appended to the
  /// cached list. The campaign `code` is unique server-side, so a duplicate
  /// replay parks as `failed` for review instead of double-discounting.
  Future<CampaignModel> storeCampaign({
    required String name,
    required String code,
    required String type,
    required double value,
    double minOrderAmount = 0,
    bool isActive = true,
  }) async {
    final body = <String, dynamic>{
      'name': name,
      'code': code,
      'type': type,
      'value': value.toStringAsFixed(2),
      'min_order_amount': minOrderAmount.toStringAsFixed(2),
      'is_active': isActive,
    };

    Map<String, dynamic>? created;
    final queued = await _gateway.queueWhenOffline(
      method: 'POST',
      path: ApiConfig.campaigns,
      data: body,
      label: 'Campaign $code',
      send: () async {
        try {
          final response = await _client.post<dynamic>(
            ApiConfig.campaigns,
            data: body,
          );
          created = toMap(toMap(response.data)['campaign']);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _gateway.cache.upsertWhere(
        prefix: CacheKeys.campaigns,
        listKey: CacheKeys.campaignsListKey,
        match: (row) => false, // always append a fresh local campaign
        row: <String, dynamic>{
          'id': -DateTime.now().millisecondsSinceEpoch,
          'name': name,
          'code': code,
          'type': type,
          'value': value.toStringAsFixed(2),
          'min_order_amount': minOrderAmount.toStringAsFixed(2),
          'is_active': isActive,
        },
      );
      throw OfflineQueuedException(label: 'Campaign');
    }
    return CampaignModel.fromJson(created ?? const {});
  }

  /// PATCH /api/campaigns/{campaign} — edit / toggle a campaign (admin only).
  Future<CampaignModel> updateCampaign({
    required int campaignId,
    String? name,
    String? type,
    double? value,
    double? minOrderAmount,
    bool? isActive,
  }) async {
    final path = ApiConfig.campaign(campaignId);
    final body = <String, dynamic>{
      'name': ?name,
      'type': ?type,
      if (value != null) 'value': value.toStringAsFixed(2),
      if (minOrderAmount != null)
        'min_order_amount': minOrderAmount.toStringAsFixed(2),
      'is_active': ?isActive,
    };

    Map<String, dynamic>? updated;
    final queued = await _gateway.queueWhenOffline(
      method: 'PATCH',
      path: path,
      data: body,
      label: 'Campaign update',
      send: () async {
        try {
          final response = await _client.patch<dynamic>(path, data: body);
          updated = toMap(toMap(response.data)['campaign']);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _gateway.cache.patchWhere(
        prefix: CacheKeys.campaigns,
        listKey: CacheKeys.campaignsListKey,
        match: (row) => toInt(row['id']) == campaignId,
        patch: body,
      );
      throw OfflineQueuedException(label: 'Campaign update');
    }
    return CampaignModel.fromJson(updated ?? const {});
  }

  /// DELETE /api/campaigns/{campaign} — remove a campaign (admin only).
  Future<void> destroyCampaign({required int campaignId}) async {
    final path = ApiConfig.campaign(campaignId);
    final queued = await _gateway.queueWhenOffline(
      method: 'DELETE',
      path: path,
      data: null,
      label: 'Campaign removed',
      send: () async {
        try {
          await _client.delete<dynamic>(path);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _gateway.cache.patchWhere(
        prefix: CacheKeys.campaigns,
        listKey: CacheKeys.campaignsListKey,
        match: (row) => toInt(row['id']) == campaignId,
        remove: true,
      );
      throw OfflineQueuedException(label: 'Campaign delete');
    }
  }

  // ------------------------------------------------------------------
  // Staff (admin only)
  // ------------------------------------------------------------------

  /// GET /api/staff — store staff list (admin only).
  Future<List<StaffMemberModel>> fetchStaff() async {
    final read = await _gateway.readRaw(
      key: CacheKeys.staff,
      source: OfflineSources.staff,
      fetch: () async {
        try {
          final response = await _client.get<dynamic>(ApiConfig.staff);
          return response.data;
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );
    return toListOfMaps(toMap(read.data)['staff'])
        .map(StaffMemberModel.fromJson)
        .toList(growable: false);
  }

  /// POST /api/staff — add a cashier (admin only; role is always cashier).
  ///
  /// Offline: queued. `email` is unique server-side, so a duplicate replay
  /// parks as `failed` instead of creating a second account.
  Future<StaffMemberModel> storeStaff({
    required String name,
    required String email,
    required String password,
    String phone = '',
  }) async {
    final body = <String, dynamic>{
      'name': name,
      'email': email,
      'password': password,
      if (phone.trim().isNotEmpty) 'phone': phone.trim(),
    };

    Map<String, dynamic>? created;
    final queued = await _gateway.queueWhenOffline(
      method: 'POST',
      path: ApiConfig.staff,
      data: body,
      label: 'New cashier $name',
      send: () async {
        try {
          final response = await _client.post<dynamic>(
            ApiConfig.staff,
            data: body,
          );
          created = toMap(toMap(response.data)['user']);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _gateway.cache.upsertWhere(
        prefix: CacheKeys.staff,
        listKey: CacheKeys.staffListKey,
        match: (row) => false, // always append a fresh local cashier
        row: <String, dynamic>{
          'id': -DateTime.now().millisecondsSinceEpoch,
          'name': name,
          'email': email,
          'phone': phone,
          'role': 'cashier',
          'is_active': true,
          'last_login_at': null,
        },
      );
      throw OfflineQueuedException(label: 'New cashier');
    }
    // The password never touches the cache — only the sanitized profile.
    return StaffMemberModel.fromJson(created ?? const {});
  }

  /// PATCH /api/staff/{user} — activate / deactivate a staff account
  /// (admin only; the server refuses deactivating your own account).
  Future<StaffMemberModel> updateStaff({
    required int userId,
    required bool isActive,
  }) async {
    final path = ApiConfig.staffMember(userId);
    final body = <String, dynamic>{'is_active': isActive};

    Map<String, dynamic>? updated;
    final queued = await _gateway.queueWhenOffline(
      method: 'PATCH',
      path: path,
      data: body,
      label: 'Staff update',
      send: () async {
        try {
          final response = await _client.patch<dynamic>(path, data: body);
          updated = toMap(toMap(response.data)['user']);
        } on DioException catch (error) {
          throw apiExceptionFrom(error);
        }
      },
    );

    if (queued) {
      await _gateway.cache.patchWhere(
        prefix: CacheKeys.staff,
        listKey: CacheKeys.staffListKey,
        match: (row) => toInt(row['id']) == userId,
        patch: body,
      );
      throw OfflineQueuedException(label: 'Staff update');
    }
    return StaffMemberModel.fromJson(updated ?? const {});
  }
}
