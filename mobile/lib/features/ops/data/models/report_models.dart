import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';

/// One hour bucket of `GET /api/reports/hourly` — the owner's day curve.
/// Always 24 rows (`00:00` … `23:00`), zero-filled server-side.
class HourlyRow extends Equatable {
  const HourlyRow({required this.hour, required this.bills, required this.revenue});

  /// `"08:00"` style label straight from the API.
  final String hour;
  final int bills;
  final double revenue;

  factory HourlyRow.fromJson(Map<String, dynamic> json) => HourlyRow(
        hour: toNullableString(json['hour'], fallback: '00:00'),
        bills: toInt(json['bills']),
        revenue: toDouble(json['revenue']),
      );

  @override
  List<Object?> get props => [hour, bills, revenue];
}

/// One row of `GET /api/reports/best-sellers` — items ranked by quantity.
class BestSeller extends Equatable {
  const BestSeller({
    required this.name,
    required this.quantity,
    required this.revenue,
  });

  final String name;
  final int quantity;
  final double revenue;

  factory BestSeller.fromJson(Map<String, dynamic> json) => BestSeller(
        name: toNullableString(json['name']),
        quantity: toInt(json['quantity']),
        revenue: toDouble(json['revenue']),
      );

  @override
  List<Object?> get props => [name, quantity, revenue];
}

/// KPI rollup from `GET /api/reports/range` — settled bills only
/// (voids excluded server-side), money as `decimal:2` strings.
class RangeReport extends Equatable {
  const RangeReport({
    required this.from,
    required this.to,
    required this.bills,
    required this.revenue,
    required this.discounts,
    required this.refunds,
    required this.net,
    required this.averageBill,
  });

  final String from;
  final String to;
  final int bills;
  final double revenue;
  final double discounts;
  final double refunds;

  /// `revenue − refunds` (server-computed with bcmath).
  final double net;
  final double averageBill;

  factory RangeReport.fromJson(Map<String, dynamic> json) => RangeReport(
        from: toNullableString(json['from']),
        to: toNullableString(json['to']),
        bills: toInt(json['bills']),
        revenue: toDouble(json['revenue']),
        discounts: toDouble(json['discounts']),
        refunds: toDouble(json['refunds']),
        net: toDouble(json['net']),
        averageBill: toDouble(json['average_bill']),
      );

  @override
  List<Object?> get props => [
        from,
        to,
        bills,
        revenue,
        discounts,
        refunds,
        net,
        averageBill,
      ];
}

/// `{date, hourly[]}` envelope of `GET /api/reports/hourly`.
class ReportHourlyBundle extends Equatable {
  const ReportHourlyBundle({required this.date, required this.rows});

  final String date;
  final List<HourlyRow> rows;

  @override
  List<Object?> get props => [date, rows];
}
