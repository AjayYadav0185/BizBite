import 'package:dio/dio.dart';

import '../../../../core/config/api_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/network/dio_client.dart';
import '../../../../core/utils/parse_utils.dart';
import '../models/console_models.dart';
import '../models/order_queue_models.dart';
import '../models/report_models.dart';
import '../models/shift_models.dart';

/// Typed gateway for every ops endpoint in `routes/api.php` NOT already
/// owned by the POS billing flow:
///
///   Order queue   GET    /orders (+ ?status=open&type=…)
///                 PATCH  /orders/{id}/status
///                 POST   /orders/{id}/refund
///                 PATCH  /orders/{id}/delivery
///   Shifts        GET    /shifts · POST /shifts/open · POST /shifts/{id}/close
///   Reports       GET    /reports/hourly · /reports/best-sellers · /reports/range
///   Tables        GET    /tables · POST · PATCH/{id} · DELETE/{id}   (admin writes)
///   Campaigns     GET    /campaigns · POST · PATCH/{id} · DELETE/{id} (admin writes)
///   Staff         GET    /staff · POST · PATCH/{id}                  (admin only)
///
/// Like every other repository it throws exactly one failure type
/// ([ApiException]) so controllers can render cashier-ready messages.
class OpsRepository {
  OpsRepository({required this._client});

  final DioClient _client;

  // ------------------------------------------------------------------
  // Order queue
  // ------------------------------------------------------------------

  /// GET /api/orders — today's bills for the caller's store, oldest first.
  /// [openOnly] maps to `?status=open` (pending/preparing/ready only);
  /// [type] maps to `?type=dine_in|takeaway|parcel|delivery`.
  Future<List<OrderQueueOrder>> fetchQueue({
    bool openOnly = false,
    String? type,
  }) async {
    try {
      final response = await _client.get<dynamic>(
        ApiConfig.orders,
        query: {
          if (openOnly) 'status': 'open',
          if (type != null && type.isNotEmpty) 'type': type,
        },
      );
      final map = toMap(response.data);
      return toListOfMaps(map['orders'])
          .map(OrderQueueOrder.fromJson)
          .toList(growable: false);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// PATCH /api/orders/{order}/status — advance or void a bill.
  Future<OrderQueueOrder> updateStatus({
    required int orderId,
    required String status,
  }) async {
    try {
      final response = await _client.patch<dynamic>(
        ApiConfig.orderStatus(orderId),
        data: {'status': status},
      );
      return OrderQueueOrder.fromJson(toMap(toMap(response.data)['order']));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// POST /api/orders/{order}/refund — partial refund with reason
  /// (ledger + negative payment leg + audit row, server-validated).
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
      throw apiExceptionFrom(error);
    }
  }

  /// PATCH /api/orders/{order}/delivery — delivery workflow move.
  Future<OrderQueueOrder> updateDelivery({
    required int orderId,
    required String deliveryStatus,
    String? deliveryAgent,
    String? deliveryAddress,
  }) async {
    try {
      final response = await _client.patch<dynamic>(
        ApiConfig.orderDelivery(orderId),
        data: {
          'delivery_status': deliveryStatus,
          if (deliveryAgent != null && deliveryAgent.trim().isNotEmpty)
            'delivery_agent': deliveryAgent.trim(),
          if (deliveryAddress != null && deliveryAddress.trim().isNotEmpty)
            'delivery_address': deliveryAddress.trim(),
        },
      );
      return OrderQueueOrder.fromJson(toMap(toMap(response.data)['order']));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }


  // ------------------------------------------------------------------
  // Shifts (cash drawer)
  // ------------------------------------------------------------------

  /// GET /api/shifts — latest 30 sessions, newest `opened_at` first.
  Future<List<Shift>> fetchShifts() async {
    try {
      final response = await _client.get<dynamic>(ApiConfig.shifts);
      return toListOfMaps(toMap(response.data)['shifts'])
          .map(Shift.fromJson)
          .toList(growable: false);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// POST /api/shifts/open — start a drawer session (201 Created).
  Future<Shift> openShift({double openingCash = 0, String? notes}) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.shiftOpen,
        data: {
          'opening_cash': openingCash.toStringAsFixed(2),
          if (notes != null && notes.trim().isNotEmpty) 'notes': notes.trim(),
        },
      );
      return Shift.fromJson(toMap(toMap(response.data)['shift']));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// POST /api/shifts/{shift}/close — count the drawer; the server computes
  /// `expected_cash` and returns the variance in the same response.
  Future<ShiftCloseResult> closeShift({
    required int shiftId,
    double closingCash = 0,
  }) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.shiftClose(shiftId),
        data: {'closing_cash': closingCash.toStringAsFixed(2)},
      );
      return ShiftCloseResult.fromJson(toMap(response.data));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }



  // ------------------------------------------------------------------
  // Reports
  // ------------------------------------------------------------------

  /// GET /api/reports/hourly — 24 zero-filled buckets for one day (Y-m-d).
  Future<ReportHourlyBundle> hourly({required String date}) async {
    try {
      final response = await _client.get<dynamic>(
        ApiConfig.reportsHourly,
        query: {'date': date},
      );
      final map = toMap(response.data);
      return ReportHourlyBundle(
        date: toNullableString(map['date'], fallback: date),
        rows: toListOfMaps(map['hourly'])
            .map(HourlyRow.fromJson)
            .toList(growable: false),
      );
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// GET /api/reports/best-sellers — items ranked by quantity for a range.
  Future<List<BestSeller>> bestSellers({
    required String from,
    required String to,
    int limit = 10,
  }) async {
    try {
      final response = await _client.get<dynamic>(
        ApiConfig.reportsBestSellers,
        query: {'from': from, 'to': to, 'limit': limit},
      );
      return toListOfMaps(toMap(response.data)['best_sellers'])
          .map(BestSeller.fromJson)
          .toList(growable: false);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// GET /api/reports/range — KPI rollup (revenue / bills / discounts /
  /// refunds / net / average bill) for a date range.
  Future<RangeReport> range({required String from, required String to}) async {
    try {
      final response = await _client.get<dynamic>(
        ApiConfig.reportsRange,
        query: {'from': from, 'to': to},
      );
      return RangeReport.fromJson(toMap(response.data));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  // ------------------------------------------------------------------
  // Tables / Campaigns / Staff (reads + admin writes for the console)
  // ------------------------------------------------------------------

  /// GET /api/tables — ordered by table_number.
  Future<List<DiningTableModel>> fetchTables() async {
    try {
      final response = await _client.get<dynamic>(ApiConfig.tables);
      return toListOfMaps(toMap(response.data)['tables'])
          .map(DiningTableModel.fromJson)
          .toList(growable: false);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// POST /api/tables — create a table (admin only, 201 Created).
  Future<DiningTableModel> storeTable({
    required String tableNumber,
    int seats = 4,
  }) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.tables,
        data: {'table_number': tableNumber, 'seats': seats},
      );
      return DiningTableModel.fromJson(toMap(toMap(response.data)['table']));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// PATCH /api/tables/{table} — status / seats update (admin only).
  /// `status: available` also clears `current_order_id` server-side.
  Future<DiningTableModel> updateTable({
    required int tableId,
    String? status,
    int? seats,
  }) async {
    try {
      final response = await _client.patch<dynamic>(
        ApiConfig.table(tableId),
        data: {
          'status': ?status,
          'seats': ?seats,
        },
      );
      return DiningTableModel.fromJson(toMap(toMap(response.data)['table']));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// DELETE /api/tables/{table} — remove a table (admin only).
  Future<void> destroyTable({required int tableId}) async {
    try {
      await _client.delete<dynamic>(ApiConfig.table(tableId));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// GET /api/campaigns — latest 100, newest first.
  Future<List<CampaignModel>> fetchCampaigns() async {
    try {
      final response = await _client.get<dynamic>(ApiConfig.campaigns);
      return toListOfMaps(toMap(response.data)['campaigns'])
          .map(CampaignModel.fromJson)
          .toList(growable: false);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// POST /api/campaigns — create a percent/flat discount code (admin only).
  Future<CampaignModel> storeCampaign({
    required String name,
    required String code,
    required String type,
    required double value,
    double minOrderAmount = 0,
    bool isActive = true,
  }) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.campaigns,
        data: {
          'name': name,
          'code': code,
          'type': type,
          'value': value.toStringAsFixed(2),
          'min_order_amount': minOrderAmount.toStringAsFixed(2),
          'is_active': isActive,
        },
      );
      return CampaignModel.fromJson(toMap(toMap(response.data)['campaign']));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
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
    try {
      final response = await _client.patch<dynamic>(
        ApiConfig.campaign(campaignId),
        data: {
          'name': ?name,
          'type': ?type,
          if (value != null) 'value': value.toStringAsFixed(2),
          if (minOrderAmount != null)
            'min_order_amount': minOrderAmount.toStringAsFixed(2),
          'is_active': ?isActive,
        },
      );
      return CampaignModel.fromJson(toMap(toMap(response.data)['campaign']));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// DELETE /api/campaigns/{campaign} — remove a campaign (admin only).
  Future<void> destroyCampaign({required int campaignId}) async {
    try {
      await _client.delete<dynamic>(ApiConfig.campaign(campaignId));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// GET /api/staff — store staff list (admin only).
  Future<List<StaffMemberModel>> fetchStaff() async {
    try {
      final response = await _client.get<dynamic>(ApiConfig.staff);
      return toListOfMaps(toMap(response.data)['staff'])
          .map(StaffMemberModel.fromJson)
          .toList(growable: false);
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// POST /api/staff — add a cashier (admin only; role is always cashier).
  Future<StaffMemberModel> storeStaff({
    required String name,
    required String email,
    required String password,
    String phone = '',
  }) async {
    try {
      final response = await _client.post<dynamic>(
        ApiConfig.staff,
        data: {
          'name': name,
          'email': email,
          'password': password,
          if (phone.trim().isNotEmpty) 'phone': phone.trim(),
        },
      );
      return StaffMemberModel.fromJson(toMap(toMap(response.data)['user']));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }

  /// PATCH /api/staff/{user} — activate / deactivate a staff account
  /// (admin only; the server refuses deactivating your own account).
  Future<StaffMemberModel> updateStaff({
    required int userId,
    required bool isActive,
  }) async {
    try {
      final response = await _client.patch<dynamic>(
        ApiConfig.staffMember(userId),
        data: {'is_active': isActive},
      );
      return StaffMemberModel.fromJson(toMap(toMap(response.data)['user']));
    } on DioException catch (error) {
      throw apiExceptionFrom(error);
    }
  }
}

