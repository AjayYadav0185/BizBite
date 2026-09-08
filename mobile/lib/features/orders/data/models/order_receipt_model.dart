import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';
import 'order_models.dart';
import 'store_model.dart';

/// The receipt payload returned by `POST /api/orders` (HTTP 201 Created).
///
/// Mirrors `App\Services\OrderReceipt::toArray()` on the Laravel side:
///
/// ```json
/// {
///   "message": "Order placed successfully.",
///   "order_id": 41,
///   "order_number": "001-20260908-0042",
///   "total_amount": "79.50",
///   "payment_mode": "cash",
///   "status": "completed",
///   "store": { ... },
///   "cashier": "Cashier User",
///   "placed_at": "08 Sep 2026, 12:45 PM",
///   "total_quantity": 7,
///   "items": [ { "food_item_name": "...", "quantity": 2, "price": "12.00", "subtotal": "24.00" } ]
/// }
/// ```
///
/// This object is handed straight to the ESC/POS printing pipeline (Step 4),
/// which renders it to raw bytes for 58mm/80mm thermal printers.
class OrderReceiptModel extends Equatable {
  const OrderReceiptModel({
    required this.orderId,
    required this.orderNumber,
    required this.totalAmount,
    required this.paymentMode,
    required this.status,
    required this.store,
    required this.cashier,
    required this.placedAt,
    required this.totalQuantity,
    required this.items,
  });

  final int orderId;
  final String orderNumber;
  final double totalAmount;
  final PaymentMode paymentMode;

  /// Backend guarantees 'completed'; kept as string for forward safety.
  final String status;

  final StoreModel store;
  final String cashier;
  final String placedAt;
  final int totalQuantity;
  final List<OrderItemModel> items;

  factory OrderReceiptModel.fromJson(Map<String, dynamic> json) {
    return OrderReceiptModel(
      orderId: toInt(json['order_id']),
      orderNumber: toNullableString(json['order_number']),
      totalAmount: toDouble(json['total_amount']),
      paymentMode: paymentModeFromJson(json['payment_mode']?.toString()),
      status: toNullableString(json['status']),
      store: StoreModel.fromJson(toMap(json['store'])),
      cashier: toNullableString(json['cashier']),
      placedAt: toNullableString(json['placed_at']),
      totalQuantity: toInt(json['total_quantity']),
      items: toListOfMaps(json['items'])
          .map(OrderItemModel.fromJson)
          .toList(growable: false),
    );
  }

  Map<String, dynamic> toJson() => {
        'order_id': orderId,
        'order_number': orderNumber,
        'total_amount': totalAmount.toStringAsFixed(2),
        'payment_mode': paymentMode.wireValue,
        'status': status,
        'store': store.toJson(),
        'cashier': cashier,
        'placed_at': placedAt,
        'total_quantity': totalQuantity,
        'items': items.map((item) => item.toJson()).toList(),
      };

  @override
  List<Object?> get props => [
        orderId,
        orderNumber,
        totalAmount,
        paymentMode,
        status,
        store,
        cashier,
        placedAt,
        totalQuantity,
        items,
      ];
}
