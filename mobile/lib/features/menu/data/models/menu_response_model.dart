import 'package:equatable/equatable.dart';

import 'category_model.dart';
import 'food_item_model.dart';

/// Aggregate envelope of `GET /api/menu`:
/// `{"categories": [...], "items": [...]}`
class MenuResponseModel extends Equatable {
  const MenuResponseModel({
    required this.categories,
    required this.items,
  });

  final List<CategoryModel> categories;
  final List<FoodItemModel> items;

  /// Convenience: items belonging to a category, already ordered by name
  /// (the backend pre-sorts). Used by the POS grid's category chips.
  List<FoodItemModel> itemsIn(int categoryId) =>
      items.where((item) => item.categoryId == categoryId).toList();

  factory MenuResponseModel.fromJson(Map<String, dynamic> json) {
    return MenuResponseModel(
      categories: _parseList(json['categories'], CategoryModel.fromJson),
      items: _parseList(json['items'], FoodItemModel.fromJson),
    );
  }

  static List<T> _parseList<T>(
    dynamic raw,
    T Function(Map<String, dynamic>) fromJson,
  ) {
    if (raw is! List) return const [];
    return raw
        .whereType<Map>()
        .map((map) => fromJson(map.cast<String, dynamic>()))
        .toList();
  }

  @override
  List<Object?> get props => [categories, items];
}
