import 'package:flutter/material.dart';

import '../theme/bizbite_theme.dart';

/// Shared category visuals for the POS quick-order grid and the Store
/// console — one place so both surfaces color-code food families
/// identically.
///
/// Tints derive from the single-source [AppColors] brand/status tokens
/// (spec §3) so a re-skin in `bizbite_theme.dart` flows through here too.

const List<Color> categoryThumbFills = [
  AppColors.infoBg, // emerald wash
  Color(0xFFFDEBDD), // peach
  Color(0xFFFBE7E9), // soft rose
  Color(0xFFFFF3D6), // butter
  Color(0xFFF0EDF7), // lavender
  Color(0xFFEAF6F4), // mint aqua
];

const List<Color> categoryThumbInks = [
  AppColors.primary, // emerald-500
  Color(0xFFE8590C), // deep orange (food)
  Color(0xFFC41E2A), // chilli red
  Color(0xFFB7791F), // amber-700 (sweet)
  AppColors.purple,
  Color(0xFF0E7C7B), // deep teal
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