import 'package:equatable/equatable.dart';

import '../../../menu/data/models/food_item_model.dart';

/// Local in-memory representation of a cart line.
///
/// Lives ONLY on the device (Step 3 CartCubit state); it is converted to
/// [OrderLineRequest] wire format when the cashier presses "Print & Settle".
class CartLine extends Equatable {
  const CartLine({
    required this.foodItem,
    required this.quantity,
    this.note = '',
  });

  final FoodItemModel foodItem;
  final int quantity;

  /// Kitchen-facing special instruction (e.g. "no onion"). Stored locally
  /// for the POS UI; the current `POST /api/orders` contract has no line-note
  /// field, so notes are NOT serialized to the backend yet.
  final String note;

  double get lineTotal => foodItem.price * quantity;

  CartLine copyWith({int? quantity, String? note}) => CartLine(
        foodItem: foodItem,
        quantity: quantity ?? this.quantity,
        note: note ?? this.note,
      );

  @override
  List<Object?> get props => [foodItem, quantity, note];
}
