import 'package:flutter/material.dart';

/// Shared category visuals for the POS quick-order grid and the Store
/// console — one place so both surfaces color-code food families
/// identically.
///
/// Warm, appetizing thumbnail hues indexed by category id so each food
/// family keeps a stable color across sessions.

const List<Color> categoryThumbFills = [
  Color(0xFFFFF3E0), // warm cream
  Color(0xFFFFEBEE), // soft rose
  Color(0xFFE8F5E9), // mint cream
  Color(0xFFFFF8E1), // butter
  Color(0xFFEDE7F6), // lavender
  Color(0xFFE0F7FA), // aqua
];

const List<Color> categoryThumbInks = [
  Color(0xFFBF360C),
  Color(0xFFAD1457),
  Color(0xFF2E7D32),
  Color(0xFFEF6C00),
  Color(0xFF4527A0),
  Color(0xFF00838F),
];

Color categoryFill(int categoryId) =>
    categoryThumbFills[categoryId % categoryThumbFills.length];

Color categoryInk(int categoryId) =>
    categoryThumbInks[categoryId % categoryThumbInks.length];

/// Maps a category/item name to a recognizable glyph (keyword heuristics).
IconData categoryIcon(String name) {
  final key = name.toLowerCase();
  if (key.contains('bever') || key.contains('drink') || key.contains('chai')) {
    return Icons.local_cafe_rounded;
  }
  if (key.contains('dessert') || key.contains('sweet') || key.contains('ice')) {
    return Icons.icecream_rounded;
  }
  if (key.contains('fast') || key.contains('burger') || key.contains('pizza')) {
    return Icons.fastfood_rounded;
  }
  if (key.contains('snack')) return Icons.cookie_rounded;
  if (key.contains('main') || key.contains('thali') || key.contains('meal')) {
    return Icons.dinner_dining_rounded;
  }
  return Icons.restaurant_menu_rounded;
}