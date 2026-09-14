// BizBite ops modules regression tests.
//
// Pure-Dart tests (no platform channels): they pin the wire contracts of the
// ops endpoints — queue order parsing (decimal-string money, user relation,
// items), shift shapes, report envelopes and the console resource models.
import 'package:flutter_test/flutter_test.dart';

import 'package:bizbite/features/auth/data/models/user_model.dart';
import 'package:bizbite/features/orders/data/models/order_models.dart';
import 'package:bizbite/features/ops/data/models/console_models.dart';
import 'package:bizbite/features/ops/data/models/order_queue_models.dart';
import 'package:bizbite/features/ops/data/models/report_models.dart';
import 'package:bizbite/features/ops/data/models/shift_models.dart';
import 'package:bizbite/core/utils/parse_utils.dart';

void main() {
  test('OrderQueueOrder parses the GET /api/orders row shape', () {
    final order = OrderQueueOrder.fromJson({
      'id': 41,
      'order_number': 'B-2026-0041',
      'status': 'preparing',
      'order_type': 'dine_in',
      'payment_mode': 'upi',
      'total_amount': '540.00',
      'refunded_amount': '0.00',
      'subtotal': '550.00',
      'discount_amount': '10.00',
      'customer_name': 'Amit',
      'table_number': 'T4',
      'created_at': '2026-09-14T12:30:00.000000Z',
      'user': {'id': 3, 'name': 'Riya'},
      'items': [
        {
          'food_item_name': 'Paneer Tikka',
          'quantity': 2,
          'price': '270.00',
          'subtotal': '540.00',
        },
      ],
    });

    expect(order.orderNumber, 'B-2026-0041');
    expect(order.status, OrderStatus.preparing);
    expect(order.status.label, 'Preparing');
    expect(order.status.isOpen, isTrue);
    expect(order.orderType, OrderType.dineIn);
    expect(order.paymentMode, PaymentMode.upi);
    expect(order.totalAmount, 540);
    expect(order.cashierName, 'Riya');
    expect(order.createdAt, isNotNull);
    expect(order.items.single.foodItemName, 'Paneer Tikka');
    expect(order.items.single.subtotal, 540);
    expect(order.hasRefund, isFalse);
  });

  test('unknown statuses fall back safely instead of crashing the queue', () {
    final order = OrderQueueOrder.fromJson({
      'id': 1,
      'status': 'future_state',
      'order_type': 'delivery',
      'delivery_status': 'out',
    });
    expect(order.status, OrderStatus.pending);
    expect(order.deliveryStatus, DeliveryStatus.out);
    expect(order.deliveryStatus!.label, 'Out for delivery');
  });

  test('Shift parses open and closed rows with the user relation', () {
    final open = Shift.fromJson({
      'id': 7,
      'status': 'open',
      'opened_at': '2026-09-14T09:00:00.000000Z',
      'closed_at': null,
      'opening_cash': '500.00',
      'closing_cash': '0.00',
      'expected_cash': '0.00',
      'user': {'id': 2, 'name': 'Owner'},
    });
    expect(open.isOpen, isTrue);
    expect(open.openingCash, 500);
    expect(open.closedAt, isNull);

    final closed = Shift.fromJson({
      'id': 6,
      'status': 'closed',
      'opening_cash': '500.00',
      'closing_cash': '1220.50',
      'expected_cash': '1220.00',
    });
    expect(closed.isOpen, isFalse);
    expect(closed.closingCash, 1220.5);
  });

  test('ShiftCloseResult carries the server variance', () {
    final result = ShiftCloseResult.fromJson({
      'shift': {'id': 6, 'status': 'closed'},
      'variance': '-0.50',
    });
    expect(result.variance, -0.5);
  });

  test('report envelopes parse decimal-string money', () {
    final bundle = ReportHourlyBundle(
      date: '2026-09-14',
      rows: toListOfMaps([
        {'hour': '09:00', 'bills': 3, 'revenue': '1250.00'},
      ]).map(HourlyRow.fromJson).toList(),
    );
    expect(bundle.date, '2026-09-14');
    expect(bundle.rows.single.bills, 3);
    expect(bundle.rows.single.revenue, 1250);

    final seller = BestSeller.fromJson({
      'name': 'Masala Dosa',
      'quantity': 12,
      'revenue': '2160.00',
    });
    expect(seller.quantity, 12);
    expect(seller.revenue, 2160);

    final range = RangeReport.fromJson({
      'from': '2026-09-14',
      'to': '2026-09-14',
      'bills': 21,
      'revenue': '9420.75',
      'discounts': '310.00',
      'refunds': '120.00',
      'net': '9300.75',
      'average_bill': '448.60',
    });
    expect(range.bills, 21);
    expect(range.net, 9300.75);
    expect(range.averageBill, 448.6);
  });

  test('console models map status vocabularies and labels', () {
    final table = DiningTableModel.fromJson({
      'id': 2,
      'table_number': 'T4',
      'seats': 6,
      'status': 'occupied',
      'current_order_id': 41,
    });
    expect(table.status, TableStatus.occupied);
    expect(table.status.label, 'Occupied');
    expect(table.isAvailable, isFalse);

    final campaign = CampaignModel.fromJson({
      'id': 5,
      'name': 'Opening week',
      'code': 'WELCOME10',
      'type': 'percent',
      'value': '10.00',
      'min_order_amount': '200.00',
      'is_active': 1,
    });
    expect(campaign.type, CampaignType.percent);
    expect(campaign.isActive, isTrue);
    expect(campaign.valueLabel, '10% off');

    final staff = StaffMemberModel.fromJson({
      'id': 9,
      'name': 'Riya',
      'email': 'riya@bizbite.test',
      'role': 'cashier',
      'is_active': true,
      'last_login_at': null,
    });
    expect(staff.role, UserRole.cashier);
    expect(staff.isActive, isTrue);
    expect(staff.lastLoginAt, isNull);
  });
}
