import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';

/// Mirror of `App\Models\Enums\PaymentMode` on the Laravel side.
/// `POST /api/orders` validates: `in:cash,upi,card`.
enum PaymentMode { cash, upi, card }

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
        PaymentMode.cash => 'CASH',
        PaymentMode.upi => 'UPI',
        PaymentMode.card => 'CARD',
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
/// `{"payment_mode": "cash", "items": [{"id": 12, "quantity": 2}]}`
class PlaceOrderRequest extends Equatable {
  const PlaceOrderRequest({
    required this.paymentMode,
    required this.items,
  });

  final PaymentMode paymentMode;
  final List<OrderLineRequest> items;

  Map<String, dynamic> toJson() => {
        'payment_mode': paymentMode.wireValue,
        'items': items.map((line) => line.toJson()).toList(),
      };

  @override
  List<Object?> get props => [paymentMode, items];
}
