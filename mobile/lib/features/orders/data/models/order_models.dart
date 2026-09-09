import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';

/// Mirror of `App\Models\Enums\PaymentMode` on the Laravel side.
/// `POST /api/orders` validates: `in:cash,upi,card,credit,split`.
enum PaymentMode { cash, upi, card, credit, split }

PaymentMode paymentModeFromJson(String? value) =>
    PaymentMode.values.firstWhere(
      (mode) => mode.name == value,
      orElse: () => PaymentMode.cash,
    );

extension PaymentModeX on PaymentMode {
  /// Wire value sent to `POST /api/orders`.
  String get wireValue => name;

  /// Label shown on the POS payment toggle.
  String get label => switch (this) {
        PaymentMode.cash => 'Cash',
        PaymentMode.upi => 'UPI',
        PaymentMode.card => 'Card',
        PaymentMode.credit => 'Credit',
        PaymentMode.split => 'Split',
      };
}

/// Mirror of `App\Models\Enums\OrderType` on the Laravel side.
/// `POST /api/orders` validates: `in:dine_in,takeaway,parcel,delivery`.
enum OrderType { dineIn, takeaway, parcel, delivery }

OrderType orderTypeFromJson(String? value) =>
    OrderType.values.firstWhere(
      (type) => type.name == value,
      orElse: () => OrderType.takeaway,
    );

extension OrderTypeX on OrderType {
  /// Wire value sent to `POST /api/orders` (snake_case like the backend).
  String get wireValue => switch (this) {
        OrderType.dineIn => 'dine_in',
        OrderType.takeaway => 'takeaway',
        OrderType.parcel => 'parcel',
        OrderType.delivery => 'delivery',
      };

  String get label => switch (this) {
        OrderType.dineIn => 'Dine-in',
        OrderType.takeaway => 'Takeaway',
        OrderType.parcel => 'Parcel',
        OrderType.delivery => 'Delivery',
      };
}

/// A single bill line item as persisted (and echoed back) by the backend.
/// Prices/subtotals arrive as `decimal:2` strings — parsed via ParseUtils.
class OrderItemModel extends Equatable {
  const OrderItemModel({
    required this.foodItemName,
    required this.quantity,
    required this.price,
    required this.subtotal,
  });

  /// Server-side snapshot of the item name at billing time.
  final String foodItemName;
  final int quantity;
  final double price;
  final double subtotal;

  factory OrderItemModel.fromJson(Map<String, dynamic> json) =>
      OrderItemModel(
        foodItemName: (json['food_item_name'] ?? json['name'] ?? '')
            .toString(),
        quantity: toInt(json['quantity']),
        price: toDouble(json['price']),
        subtotal: toDouble(json['subtotal'] ?? json['line_total']),
      );

  Map<String, dynamic> toJson() => {
        'food_item_name': foodItemName,
        'quantity': quantity,
        'price': price.toStringAsFixed(2),
        'subtotal': subtotal.toStringAsFixed(2),
      };

  @override
  List<Object?> get props => [foodItemName, quantity, price, subtotal];
}

/// One line of the OUTGOING cart when calling `POST /api/orders`.
///
/// Serializes to `{"id": 12, "quantity": 2}` — the API also accepts the
/// long-form alias `food_item_id`; the Laravel controller normalizes both.
class OrderLineRequest extends Equatable {
  const OrderLineRequest({required this.id, required this.quantity});

  final int id;
  final int quantity;

  Map<String, dynamic> toJson() => {'id': id, 'quantity': quantity};

  @override
  List<Object?> get props => [id, quantity];
}

/// Full request body for `POST /api/orders`:
///
/// ```json
/// {
///   "payment_mode": "upi",
///   "order_type": "takeaway",
///   "discount_amount": "20.00",
///   "customer_name": "Amit",
///   "customer_phone": "98110XXXXX",
///   "upi_ref": "UTR / UPI txn id",
///   "idempotency_key": "client-generated uuid (safe to retry)",
///   "items": [{ "id": 12, "quantity": 2 }]
/// }
/// ```
class PlaceOrderRequest extends Equatable {
  const PlaceOrderRequest({
    required this.paymentMode,
    required this.items,
    this.orderType = OrderType.takeaway,
    this.discountAmount = 0.0,
    this.customerName = '',
    this.customerPhone = '',
    this.upiRef = '',
    required this.idempotencyKey,
  });

  final PaymentMode paymentMode;
  final OrderType orderType;
  final List<OrderLineRequest> items;
  final double discountAmount;
  final String customerName;
  final String customerPhone;
  final String upiRef;
  final String idempotencyKey;

  Map<String, dynamic> toJson() => {
        'payment_mode': paymentMode.wireValue,
        'order_type': orderType.wireValue,
        'items': items.map((line) => line.toJson()).toList(),
        if (discountAmount > 0) 'discount_amount': discountAmount.toStringAsFixed(2),
        if (customerName.trim().isNotEmpty) 'customer_name': customerName.trim(),
        if (customerPhone.trim().isNotEmpty) 'customer_phone': customerPhone.trim(),
        if (upiRef.trim().isNotEmpty) 'upi_ref': upiRef.trim(),
        'idempotency_key': idempotencyKey,
      };

  @override
  List<Object?> get props => [
        paymentMode,
        orderType,
        items,
        discountAmount,
        customerName,
        customerPhone,
        upiRef,
        idempotencyKey,
      ];
}
