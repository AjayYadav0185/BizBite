import 'package:flutter/foundation.dart';

import '../../core/network/api_exception.dart';
import '../../core/sync/offline_queued_exception.dart';
import 'data/models/console_models.dart';
import 'data/repositories/ops_repository.dart';

/// State holder for the owner console's Tables / Campaigns / Staff tabs.
///
/// Reads are available to both roles (route-level `role:admin,cashier`);
/// every write below is admin-only on the backend (`role:admin` group) and
/// surfaced as a 403 [ApiException] for cashiers, which the UI maps to a
/// friendly message.
///
/// Offline: reads come from the SQLite cache (last-good floor plan / campaign
/// list) and every write is queued in the mutation outbox with the cached row
/// patched immediately, so the console stays fully usable without a link.
class ConsoleController extends ChangeNotifier {
  ConsoleController({required this._repository});

  final OpsRepository _repository;

  List<DiningTableModel> tables = const [];
  List<CampaignModel> campaigns = const [];
  List<StaffMemberModel> staff = const [];

  bool loading = false;
  bool mutating = false;
  String error = '';

  /// Set when the last write was queued for sync instead of reaching the
  /// server, so the screen can confirm it to the owner.
  String? queuedNotice;

  Future<void> load() async {
    if (loading) return;
    loading = true;
    error = '';
    notifyListeners();

    try {
      // Tables/campaigns load for both roles; staff may 403 for a cashier —
      // tolerate it so the console still opens with its other tabs.
      final results = await Future.wait([
        _repository.fetchTables(),
        _repository.fetchCampaigns(),
        _repository.fetchStaff().catchError((Object _) => const <StaffMemberModel>[]),
      ]);
      tables = results[0] as List<DiningTableModel>;
      campaigns = results[1] as List<CampaignModel>;
      staff = results[2] as List<StaffMemberModel>;
      error = '';
    } on ApiException catch (e) {
      error = e.message;
      if (e.isAuthError) rethrow;
    } catch (_) {
      error = 'Could not load the console.';
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  // ------------------------------------------------------------------
  // Tables
  // ------------------------------------------------------------------

  Future<String?> addTable({required String tableNumber, required int seats}) async {
    return _mutate(() async {
      await _repository.storeTable(tableNumber: tableNumber, seats: seats);
      tables = await _repository.fetchTables();
    });
  }

  Future<String?> setTableStatus(DiningTableModel table, TableStatus status) async {
    return _mutate(() async {
      await _repository.updateTable(
        tableId: table.id,
        status: status.name,
        seats: table.seats,
      );
      tables = await _repository.fetchTables();
    });
  }

  Future<String?> deleteTable(DiningTableModel table) async {
    return _mutate(() async {
      await _repository.destroyTable(tableId: table.id);
      tables = await _repository.fetchTables();
    });
  }

  // ------------------------------------------------------------------
  // Campaigns
  // ------------------------------------------------------------------

  Future<String?> addCampaign({
    required String name,
    required String code,
    required CampaignType type,
    required double value,
    double minOrderAmount = 0,
  }) async {
    return _mutate(() async {
      await _repository.storeCampaign(
        name: name,
        code: code,
        type: type.name,
        value: value,
        minOrderAmount: minOrderAmount,
      );
      campaigns = await _repository.fetchCampaigns();
    });
  }

  /// Toggle a campaign's `is_active` flag.
  Future<String?> toggleCampaign(CampaignModel campaign) async {
    return _mutate(() async {
      await _repository.updateCampaign(
        campaignId: campaign.id,
        isActive: !campaign.isActive,
      );
      campaigns = await _repository.fetchCampaigns();
    });
  }

  Future<String?> deleteCampaign(CampaignModel campaign) async {
    return _mutate(() async {
      await _repository.destroyCampaign(campaignId: campaign.id);
      campaigns = await _repository.fetchCampaigns();
    });
  }

  // ------------------------------------------------------------------
  // Staff
  // ------------------------------------------------------------------

  Future<String?> addStaff({
    required String name,
    required String email,
    required String password,
    String phone = '',
  }) async {
    return _mutate(() async {
      await _repository.storeStaff(
        name: name,
        email: email,
        password: password,
        phone: phone,
      );
      staff = await _repository.fetchStaff();
    });
  }

  /// Activate / deactivate a cashier (the server blocks self-deactivation).
  Future<String?> setStaffActive(StaffMemberModel member, bool isActive) async {
    return _mutate(() async {
      await _repository.updateStaff(userId: member.id, isActive: isActive);
      staff = await _repository.fetchStaff();
    });
  }

  Future<String?> _mutate(Future<void> Function() action) async {
    if (mutating) return null;
    mutating = true;
    queuedNotice = null;
    notifyListeners();
    try {
      await action();
      return null;
    } on OfflineQueuedException catch (queued) {
      // Durable locally + the cached console rows were patched: success.
      queuedNotice = queued.message;
      return null;
    } on ApiException catch (e) {
      return e.type == ApiExceptionType.forbidden
          ? 'Only the store owner can make this change.'
          : e.message;
    } catch (_) {
      return 'Something went wrong. Please try again.';
    } finally {
      mutating = false;
      notifyListeners();
    }
  }
}
