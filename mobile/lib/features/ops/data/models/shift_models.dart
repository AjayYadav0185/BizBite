import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';

/// Mirror of `App\Models\Shift` status constants (`open` / `closed`).
enum ShiftStatus { open, closed }

ShiftStatus shiftStatusFromJson(String? value) => ShiftStatus.values.firstWhere(
      (status) => status.name == value,
      orElse: () => ShiftStatus.closed,
    );

/// A cash-drawer session as returned by `GET /api/shifts`
/// (latest 30, newest `opened_at` first, with the `user:id,name` relation).
class Shift extends Equatable {
  const Shift({
    required this.id,
    required this.status,
    required this.openedAt,
    required this.openingCash,
    required this.closingCash,
    required this.expectedCash,
    required this.userName,
    this.closedAt,
    this.notes = '',
  });

  final int id;
  final ShiftStatus status;
  final DateTime? openedAt;
  final DateTime? closedAt;

  /// Drawer float counted when the shift opened.
  final double openingCash;

  /// Drawer cash counted at close (`0.00` while still open).
  final double closingCash;

  /// Server-computed expected drawer total at close time.
  final double expectedCash;
  final String notes;
  final String userName;

  bool get isOpen => status == ShiftStatus.open;

  factory Shift.fromJson(Map<String, dynamic> json) => Shift(
        id: toInt(json['id']),
        status: shiftStatusFromJson(json['status']?.toString()),
        openedAt: DateTime.tryParse('${json['opened_at']}'),
        closedAt: DateTime.tryParse('${json['closed_at']}'),
        openingCash: toDouble(json['opening_cash']),
        closingCash: toDouble(json['closing_cash']),
        expectedCash: toDouble(json['expected_cash']),
        notes: toNullableString(json['notes']),
        userName: toNullableString(toMap(json['user'])['name']),
      );

  @override
  List<Object?> get props => [
        id,
        status,
        openedAt,
        closedAt,
        openingCash,
        closingCash,
        expectedCash,
        userName,
      ];
}

/// Result of `POST /api/shifts/{shift}/close` — the closed shift plus the
/// server-side variance (`closing_cash − expected_cash`; negative = short).
class ShiftCloseResult extends Equatable {
  const ShiftCloseResult({required this.shift, required this.variance});

  final Shift shift;
  final double variance;

  factory ShiftCloseResult.fromJson(Map<String, dynamic> json) =>
      ShiftCloseResult(
        shift: Shift.fromJson(toMap(json['shift'])),
        variance: toDouble(json['variance']),
      );

  @override
  List<Object?> get props => [shift, variance];
}
