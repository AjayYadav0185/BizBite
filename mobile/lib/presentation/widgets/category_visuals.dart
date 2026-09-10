import 'package:flutter/material.dart';

import '../theme/bizbite_theme.dart';

/// Shared category visuals for the POS quick-order grid and the Store
/// console — one place so both surfaces color-code food families
/// identically.
///
/// Tints derive from the single-source [AppColors] brand/status tokens
/// (spec §3) so a re-skin in `bizbite_theme.dart` flows through here too.

const List<Color> categoryThumbFills = [
  AppColors.infoBg, // brand tint
  Color(0xFFFFEBEE), // soft rose
  AppColors.successBg, // mint cream
  AppColors.warningBg, // butter
  Color(0xFFEDE7F6), // lavender
  Color(0xFFE0F7FA), // aqua
];

const List<Color> categoryThumbInks = [
  AppColors.primary,
  Color(0xFFAD1457),
  AppColors.successDeep,
  AppColors.warningDeep,
  AppColors.purple,
  AppColors.infoCyan,
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