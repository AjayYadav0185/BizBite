import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';
import '../../../orders/data/models/order_models.dart';

/// Mirror of `App\Models\Enums\OrderStatus` on the Laravel side.
/// `PATCH /api/orders/{order}/status` validates: `in:pending,preparing,ready,completed,cancelled`.
enum OrderStatus { pending, preparing, ready, completed, cancelled }

OrderStatus orderStatusFromJson(String? value) => OrderStatus.values.firstWhere(
      (status) => status.name == value,
      orElse: () => OrderStatus.pending,
    );

extension OrderStatusX on OrderStatus {
  /// Label shown on the kitchen/counter queue chips.
  String get label => switch (this) {
        OrderStatus.pending => 'Pending',
        OrderStatus.preparing => 'Preparing',
        OrderStatus.ready => 'Ready',
        OrderStatus.completed => 'Completed',
        OrderStatus.cancelled => 'Cancelled',
      };

  /// True while the bill is still on the floor (the `?status=open` filter).
  bool get isOpen =>
      this == OrderStatus.pending ||
      this == OrderStatus.preparing ||
      this == OrderStatus.ready;
}

/// Mirror of the delivery workflow vocabulary accepted by
/// `PATCH /api/orders/{order}/delivery` (§4.5 delivery moves).
enum DeliveryStatus { pending, assigned, out, delivered, failed }

DeliveryStatus deliveryStatusFromJson(String? value) =>
    DeliveryStatus.values.firstWhere(
      (status) => status.name == value,
      orElse: () => DeliveryStatus.pending,
    );

extension DeliveryStatusX on DeliveryStatus {
  String get label => switch (this) {
        DeliveryStatus.pending => 'Pending',
        DeliveryStatus.assigned => 'Assigned',
        DeliveryStatus.out => 'Out for delivery',
        DeliveryStatus.delivered => 'Delivered',
        DeliveryStatus.failed => 'Failed',
      };
}

/// Wire-aware [OrderType] parser: `GET /api/orders` serialises the PHP enum
/// as its snake_case value (`dine_in`), while the Dart enum name is `dineIn`.
OrderType orderTypeFromWire(String? value) => OrderType.values.firstWhere(
      (type) => type.wireValue == value || type.name == value,
      orElse: () => OrderType.takeaway,
    );

/// One settled line of a server order (relation `items` on `GET /api/orders`).
class OrderQueueItem extends Equatable {
  const OrderQueueItem({
    required this.foodItemName,
    required this.quantity,
    required this.price,
    required this.subtotal,
  });

  final String foodItemName;
  final int quantity;
  final double price;
  final double subtotal;

  factory OrderQueueItem.fromJson(Map<String, dynamic> json) =>
      OrderQueueItem(
        foodItemName: (json['food_item_name'] ?? json['name'] ?? '').toString(),
        quantity: toInt(json['quantity']),
        price: toDouble(json['price']),
        subtotal: toDouble(json['subtotal'] ?? json['line_total']),
      );

  @override
  List<Object?> get props => [foodItemName, quantity, price, subtotal];
}

/// A bill as returned by `GET /api/orders` (today's kitchen/counter queue,
/// oldest first, limit 100, scoped to the caller's store by the StoreScope).
class OrderQueueOrder extends Equatable {
  const OrderQueueOrder({
    required this.id,
    required this.orderNumber,
    required this.status,
    required this.orderType,
    required this.paymentMode,
    required this.totalAmount,
    required this.refundedAmount,
    required this.cashierName,
    required this.createdAt,
    this.subtotal = 0,
    this.discountAmount = 0,
    this.customerName = '',
    this.customerPhone = '',
    this.tableNumber = '',
    this.notes = '',
    this.campaignCode = '',
    this.deliveryStatus,
    this.deliveryAgent = '',
    this.items = const [],
  });

  final int id;
  final String orderNumber;
  final OrderStatus status;
  final OrderType orderType;
  final PaymentMode paymentMode;
  final double totalAmount;
  final double refundedAmount;

  /// `user:id,name` relation — who billed it.
  final String cashierName;
  final DateTime? createdAt;

  final double subtotal;
  final double discountAmount;
  final String customerName;
  final String customerPhone;
  final String tableNumber;
  final String notes;
  final String campaignCode;

  /// Delivery workflow fields (only meaningful for `order_type=delivery`).
  final DeliveryStatus? deliveryStatus;
  final String deliveryAgent;

  final List<OrderQueueItem> items;

  bool get hasRefund => refundedAmount > 0;

  factory OrderQueueOrder.fromJson(Map<String, dynamic> json) {
    final deliveryRaw = toNullableString(json['delivery_status']);
    return OrderQueueOrder(
      id: toInt(json['id']),
      orderNumber: toNullableString(json['order_number']),
      status: orderStatusFromJson(json['status']?.toString()),
      orderType: orderTypeFromWire(json['order_type']?.toString()),
      paymentMode: paymentModeFromJson(json['payment_mode']?.toString()),
      totalAmount: toDouble(json['total_amount']),
      refundedAmount: toDouble(json['refunded_amount']),
      cashierName: toNullableString(
        toMap(json['user'])['name'] ?? json['cashier_name'],
      ),
      createdAt: DateTime.tryParse('${json['created_at']}'),
      subtotal: toDouble(json['subtotal']),
      discountAmount: toDouble(json['discount_amount']),
      customerName: toNullableString(json['customer_name']),
      customerPhone: toNullableString(json['customer_phone']),
      tableNumber: toNullableString(json['table_number']),
      notes: toNullableString(json['notes']),
      campaignCode: toNullableString(json['campaign_code']),
      deliveryStatus: deliveryRaw.isEmpty
          ? null
          : deliveryStatusFromJson(deliveryRaw),
      deliveryAgent: toNullableString(json['delivery_agent']),
      items: toListOfMaps(json['items'])
          .map(OrderQueueItem.fromJson)
          .toList(growable: false),
    );
  }

  @override
  List<Object?> get props => [
        id,
        orderNumber,
        status,
        orderType,
        paymentMode,
        totalAmount,
        refundedAmount,
        cashierName,
        createdAt,
        items,
      ];
}
