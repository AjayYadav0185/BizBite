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
  });

  final FoodItemModel foodItem;
  final int quantity;

  double get lineTotal => foodItem.price * quantity;

  CartLine copyWith({int? quantity}) =>
      CartLine(foodItem: foodItem, quantity: quantity ?? this.quantity);

  @override
  List<Object?> get props => [foodItem, quantity];
}
