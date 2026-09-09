import 'package:flutter/material.dart';

/// BizBite POS design system — the single source of truth for color, type,
/// shape and component styling across the billing flow.
///
/// Design rules (see docs/design/POS_UI_DESIGN_SPEC.md):
///  * Warm, appetizing palette — deep amber `#E65100` drives every primary
///    action; success/alert statuses use `#2E7D32` / `#EF6C00`.
///  * Soft off-white canvas `#F5F5F5` with pure-white cards for scannable
///    contrast; a warm dark `#1B1712` header shell for a premium till feel.
///  * Card-based layout, 12–16dp corner radii, zero visual clutter.
///  * Bold, tabular-figure numerics for prices and order counts so columns
///    of money never jitter while a cashier is scanning the bill.
abstract final class BizBiteTheme {
  // --- Brand ---------------------------------------------------------------
  /// Deep Amber — appetite-stimulating primary for CTAs and money accents.
  static const Color brand = Color(0xFFE65100);

  /// Darker amber used on the "Process & Print" emphasis states.
  static const Color brandDeep = Color(0xFFBF360C);

  /// Soft amber wash for selected chips, badges and tinted surfaces.
  static const Color brandSoft = Color(0xFFFFE0B2);

  // --- Neutrals ------------------------------------------------------------
  /// Soft off-white app canvas.
  static const Color canvas = Color(0xFFF5F5F5);

  /// Warm dark ink for the header shell / dark surfaces.
  static const Color inkDark = Color(0xFF1B1712);

  /// Muted label grey.
  static const Color inkMuted = Color(0xFF6F6A66);

  static const Color hairline = Color(0xFFE4E0DC);

  // --- Status --------------------------------------------------------------
  /// Success Green — paid / settled orders.
  static const Color success = Color(0xFF2E7D32);

  static const Color successContainer = Color(0xFFE6F4EA);

  /// Alert Amber — pending kitchen / attention states.
  static const Color alert = Color(0xFFEF6C00);

  static ThemeData light() {
    final scheme = ColorScheme.fromSeed(seedColor: brand).copyWith(
      primary: brand,
      onPrimary: Colors.white,
      primaryContainer: brandSoft,
      onPrimaryContainer: const Color(0xFF4A2000),
      secondary: brandDeep,
      onSecondary: Colors.white,
      secondaryContainer: const Color(0xFFFFDBD1),
      onSecondaryContainer: const Color(0xFF410E0B),
      surface: Colors.white,
      onSurface: const Color(0xFF201C18),
      onSurfaceVariant: inkMuted,
      surfaceContainerLowest: Colors.white,
      surfaceContainerLow: const Color(0xFFFAF9F8),
      surfaceContainer: const Color(0xFFF5F3F1),
      surfaceContainerHigh: const Color(0xFFEFEDEB),
      outlineVariant: hairline,
      error: const Color(0xFFB3261E),
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      scaffoldBackgroundColor: canvas,
      splashFactory: InkSparkle.splashFactory,
      appBarTheme: const AppBarTheme(
        backgroundColor: inkDark,
        foregroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          fontSize: 17,
          fontWeight: FontWeight.w700,
          color: Colors.white,
          letterSpacing: -0.2,
        ),
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: hairline),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(48, 52),
          padding: const EdgeInsets.symmetric(horizontal: 20),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        hintStyle: const TextStyle(color: inkMuted, fontSize: 14.5),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: hairline),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: hairline),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: brand, width: 1.6),
        ),
      ),
      segmentedButtonTheme: SegmentedButtonThemeData(
        style: SegmentedButton.styleFrom(
          visualDensity: VisualDensity.compact,
          backgroundColor: Colors.white,
          foregroundColor: inkMuted,
          selectedForegroundColor: Colors.white,
          selectedBackgroundColor: brand,
          side: const BorderSide(color: hairline),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          textStyle: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700),
        ),
      ),
      snackBarTheme: const SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.all(Radius.circular(12)),
        ),
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        type: BottomNavigationBarType.fixed,
        backgroundColor: Colors.white,
        selectedItemColor: brand,
        unselectedItemColor: inkMuted,
        selectedLabelStyle: TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
        unselectedLabelStyle: TextStyle(fontSize: 12),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: Colors.white,
        showDragHandle: true,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
      ),
    );
  }

  /// Bold price / count numerals — always tabular so totals never jitter.
  static const TextStyle numeral = TextStyle(
    fontWeight: FontWeight.w800,
    fontFeatures: [FontFeature.tabularFigures()],
  );

  /// Receipt typography — monospaced, high contrast, mimicking thermal
  /// print. Bundling `RobotoMono` gives a pixel-exact match on every device;
  /// the fallback chain keeps Menlo/Courier on other platforms.
  static TextStyle receiptMono({
    double size = 12.5,
    FontWeight weight = FontWeight.w500,
    Color? color,
    double? letterSpacing,
    double? height,
  }) {
    return TextStyle(
      fontFamily: 'RobotoMono',
      fontFamilyFallback: const ['Menlo', 'Courier New', 'monospace'],
      fontSize: size,
      fontWeight: weight,
      color: color,
      letterSpacing: letterSpacing,
      height: height,
      fontFeatures: const [FontFeature.tabularFigures()],
    );
  }
}