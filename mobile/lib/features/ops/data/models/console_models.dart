import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';
import '../../../auth/data/models/user_model.dart';

/// Mirror of `App\Models\DiningTable` status constants.
enum TableStatus { available, occupied, reserved }

TableStatus tableStatusFromJson(String? value) =>
    TableStatus.values.firstWhere(
      (status) => status.name == value,
      orElse: () => TableStatus.available,
    );

extension TableStatusX on TableStatus {
  String get label => switch (this) {
        TableStatus.available => 'Available',
        TableStatus.occupied => 'Occupied',
        TableStatus.reserved => 'Reserved',
      };
}

/// A dining table from `GET /api/tables` (ordered by table_number).
class DiningTableModel extends Equatable {
  const DiningTableModel({
    required this.id,
    required this.tableNumber,
    required this.seats,
    required this.status,
    required this.currentOrderId,
  });

  final int id;
  final String tableNumber;
  final int seats;
  final TableStatus status;

  /// Bill currently seated here (nullable server-side).
  final int currentOrderId;

  bool get isAvailable => status == TableStatus.available;

  factory DiningTableModel.fromJson(Map<String, dynamic> json) =>
      DiningTableModel(
        id: toInt(json['id']),
        tableNumber: toNullableString(json['table_number']),
        seats: toInt(json['seats'], fallback: 4),
        status: tableStatusFromJson(json['status']?.toString()),
        currentOrderId: toInt(json['current_order_id']),
      );

  @override
  List<Object?> get props => [id, tableNumber, seats, status, currentOrderId];
}

/// Mirror of `App\Models\Campaign` type constants (`percent` / `flat`).
enum CampaignType { percent, flat }

CampaignType campaignTypeFromJson(String? value) =>
    CampaignType.values.firstWhere(
      (type) => type.name == value,
      orElse: () => CampaignType.percent,
    );

extension CampaignTypeX on CampaignType {
  String get label => switch (this) {
        CampaignType.percent => 'Percent',
        CampaignType.flat => 'Flat',
      };
}

/// A discount campaign from `GET /api/campaigns` (latest 100).
class CampaignModel extends Equatable {
  const CampaignModel({
    required this.id,
    required this.name,
    required this.code,
    required this.type,
    required this.value,
    required this.minOrderAmount,
    required this.isActive,
    this.startsAt,
    this.endsAt,
  });

  final int id;
  final String name;

  /// Uppercase code the cashier types on the POS bill sheet.
  final String code;
  final CampaignType type;
  final double value;
  final double minOrderAmount;
  final bool isActive;
  final DateTime? startsAt;
  final DateTime? endsAt;

  /// Human-readable discount preview: "10% off" / "₹50 off".
  String get valueLabel => type == CampaignType.percent
      ? '${_trimZeros(value)}% off'
      : '₹${_trimZeros(value)} off';

  static String _trimZeros(double v) =>
      v == v.roundToDouble() ? v.toStringAsFixed(0) : v.toStringAsFixed(2);

  factory CampaignModel.fromJson(Map<String, dynamic> json) => CampaignModel(
        id: toInt(json['id']),
        name: toNullableString(json['name']),
        code: toNullableString(json['code']),
        type: campaignTypeFromJson(json['type']?.toString()),
        value: toDouble(json['value']),
        minOrderAmount: toDouble(json['min_order_amount']),
        isActive: json['is_active'] == true || json['is_active'] == 1,
        startsAt: DateTime.tryParse('${json['starts_at']}'),
        endsAt: DateTime.tryParse('${json['ends_at']}'),
      );

  @override
  List<Object?> get props => [
        id,
        name,
        code,
        type,
        value,
        minOrderAmount,
        isActive,
      ];
}

/// A staff member from `GET /api/staff` (admin-only endpoint). Only `name`,
/// `email`, `phone`, `role`, `is_active`, `last_login_at` are exposed —
/// never password hashes or store internals.
class StaffMemberModel extends Equatable {
  const StaffMemberModel({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.isActive,
    this.phone = '',
    this.lastLoginAt,
  });

  final int id;
  final String name;
  final String email;
  final String phone;
  final UserRole role;
  final bool isActive;
  final DateTime? lastLoginAt;

  factory StaffMemberModel.fromJson(Map<String, dynamic> json) =>
      StaffMemberModel(
        id: toInt(json['id']),
        name: toNullableString(json['name']),
        email: toNullableString(json['email']),
        phone: toNullableString(json['phone']),
        role: userRoleFromJson(json['role']?.toString()),
        isActive: json['is_active'] == true || json['is_active'] == 1,
        lastLoginAt: DateTime.tryParse('${json['last_login_at']}'),
      );

  @override
  List<Object?> get props => [id, name, email, phone, role, isActive];
}
