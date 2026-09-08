import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';

/// Sellable menu item — payload of `GET /api/menu`:
/// `"items": [{ "id": 12, "category_id": 2, "name": "Vada Pav", "price": "12.00" }, ...]`
///
/// Only AVAILABLE items are returned by the backend, so the grid never needs
/// to grey anything out client-side.
class FoodItemModel extends Equatable {
  const FoodItemModel({
    required this.id,
    required this.categoryId,
    required this.name,
    required this.price,
  });

  final int id;
  final int categoryId;
  final String name;

  /// Unit price in ₹. Parsed from Laravel's `decimal:2` string cast.
  final double price;

  factory FoodItemModel.fromJson(Map<String, dynamic> json) => FoodItemModel(
        id: toInt(json['id']),
        categoryId: toInt(json['category_id']),
        name: toNullableString(json['name']),
        price: toDouble(json['price']),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'category_id': categoryId,
        'name': name,
        'price': price,
      };

  @override
  List<Object?> get props => [id, categoryId, name, price];
}
