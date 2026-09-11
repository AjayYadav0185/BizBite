import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';

// ============================================================
// SINGLE SOURCE OF TRUTH — edit ONLY this file to re-skin.
// Mirrors mobile/docs/design/POS_UI_DESIGN_SPEC.md.
// ============================================================

/// Spec §3 — color tokens. Never hardcode hex elsewhere.
///
/// Brand language mirrors the BizBite web POS (Laravel console): a fresh
/// food-outlet **emerald green** over **charcoal slate**, warm amber
/// warnings and red errors — so the till and the console feel like one
/// system. Light mode keeps the same emerald identity on soft green-grey
/// surfaces; dark mode matches the console's slate-950/900 cards.
abstract final class AppColors {
  // --- Brand: emerald (web POS emerald-500 CTA & success flash) ----------
  static const Color primary = Color(0xFF10B981); // emerald-500
  static const Color primaryDark = Color(0xFF34D399); // emerald-400 (dark)
  static const Color primaryLight = Color(0xFF34D399); // emerald-400 (bright)
  static const Color primarySoft = Color(0xFF059669); // emerald-600 (deep)
  static const Color accentTeal = Color(0xFF14B8A6); // teal-500 tertiary
  static const Color skyTeal = Color(0xFF0D9488); // teal-600 pop accent
  static const Color purple = Color(0xFF8A4FDB); // violet-500 (menu variety)
  static const Color infoBg = Color(0xFFECFDF5); // emerald-50 brand wash
  static const Color accentSoft = Color(0xFF6EE7B7); // emerald-300 (dark)
  static const Color primaryDeep = Color(0xFF047857); // emerald-700 deepest

  // --- Ink / text: web slate scale -----------------------------------------
  static const Color ink = Color(0xFF0F172A); // slate-900
  static const Color inkDark = Color(0xFF020617); // slate-950
  static const Color muted = Color(0xFF64748B); // slate-500 light secondary
  static const Color faintMuted = Color(0xFF94A3B8); // slate-400 dark secondary
  static const Color slate700 = Color(0xFF334155);
  static const Color slate600 = Color(0xFF475569);
  static const Color slate500 = Color(0xFF64748B);
  static const Color slate400 = Color(0xFF94A3B8);
  static const Color slate800 = Color(0xFF1E293B);

  // --- Surfaces -------------------------------------------------------------
  static const Color background = Color(0xFFF5F7F6); // soft green-white canvas
  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceMuted = Color(0xFFF0F3F1);
  static const Color surfaceSoft = Color(0xFFF8FAF8);
  static const Color surfaceSoft2 = Color(0xFFF1F4F2);
  static const Color surfaceSubtle = Color(0xFFEBEFED);
  static const Color gradientStart = Color(0xFFF2F6F4);
  static const Color gradientMid = Color(0xFFE6EDE8);
  static const Color gradientEnd = Color(0xFFDCE5DF);
  static const Color darkSurface = Color(0xFF1E293B); // slate-800
  static const Color border = Color(0xFFE1E7E3);
  static const Color borderLight = Color(0xFFE2E8E4);
  static const Color borderMuted = Color(0xFFCBD5E1); // slate-300

  // --- Status -----------------------------------------------------------------
  static const Color success = Color(0xFF10B981); // emerald-500 = paid
  static const Color successDeep = Color(0xFF059669); // emerald-600
  static const Color successAlt = Color(0xFF34A853);
  static const Color successBg = Color(0xFFECFDF5); // emerald-50
  static const Color warning = Color(0xFFFF8C42); // warm food orange
  static const Color warningDeep = Color(0xFFFF6D00);
  static const Color warningBg = Color(0xFFFFF3E8); // orange-50
  static const Color error = Color(0xFFEF4444); // red-500
  static const Color errorAlt = Color(0xFFF87171); // red-400
  static const Color errorDeep = Color(0xFFDC2626); // red-600
  static const Color errorBg = Color(0xFFFEF2F2); // red-50
  static const Color infoCyan = Color(0xFF17A8C4);
  static const Color whatsapp = Color(0xFF25D366);

  // --- Dark mode (charcoal, mirrors web slate-950/900/800) -------------------
  static const Color bgDark = Color(0xFF020617); // slate-950
  static const Color cardDark = Color(0xFF0F172A); // slate-900

  // --- Thermal paper -----------------------------------------------------------
  static const Color paperInk = Color(0xFF26221E);
  static const Color paperMuted = Color(0xFF8A857F);
  static const Color printerBed = Color(0xFF161310);
}

/// Spec §5 — spacing scale.
abstract final class AppSpacing {
  static const double xs = 4;
  static const double sm = 8;
  static const double smPlus = 10;
  static const double md = 12;
  static const double lg = 16;
  static const double xl = 24;
  static const double xxl = 32;
}

/// Spec §5 — radius scale.
abstract final class AppRadius {
  static const double sm = 8;
  static const double md = 12;
  static const double lg = 16;
  static const double xl = 18;
  static const double xxl = 24;
  static const double full = 999;
}

/// Spec §5 — shadows (elevation 0, soft shadow does depth).
abstract final class AppShadows {
  static List<BoxShadow> get card => const [
        BoxShadow(
          color: Color(0x0D14151F),
          blurRadius: 12,
          offset: Offset(0, 2),
        ),
      ];
  static List<BoxShadow> get bar => const [
        BoxShadow(
          color: Color(0x1414151F),
          blurRadius: 16,
          offset: Offset(0, -4),
        ),
      ];
}

/// Spec §5 — gradients (page backdrop + brand tiles).
abstract final class AppGradients {
  static const LinearGradient page = LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [
      AppColors.gradientStart,
      AppColors.gradientMid,
      AppColors.gradientEnd,
    ],
  );
  /// Charcoal brand tile — store hero, dark panels.
  static const LinearGradient brand = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [AppColors.ink, AppColors.darkSurface],
  );
  /// Emerald brand tile — the food-outlet signature (login/splash logo,
  /// app-bar badge), matching the web POS emerald → deep-green CTA.
  static const LinearGradient brandMain = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [AppColors.primary, AppColors.primarySoft],
  );
}

/// BizBite POS design system — ThemeData + legacy aliases.
abstract final class BizBiteTheme {
  // --- Brand (legacy aliases → AppColors §3) --------------------------------
  /// Food-outlet emerald primary — matches the web POS emerald-500 CTA.
  static const Color brand = AppColors.primary;

  /// Darker emphasis legacy alias — now primarySoft (emerald-600).
  static const Color brandDeep = AppColors.primarySoft;

  /// Soft wash legacy alias — now infoBg (emerald-50).
  static const Color brandSoft = AppColors.infoBg;

  // --- Neutrals (legacy aliases → AppColors §3) ------------------------------
  /// Soft off-white app canvas.
  static const Color canvas = AppColors.background;

  /// Slate-900 ink legacy alias — now spec ink per §3.
  static const Color inkDark = AppColors.ink;

  /// Muted label grey.
  static const Color inkMuted = AppColors.muted;

  static const Color hairline = AppColors.border;

  // --- Status (legacy aliases → AppColors §3) --------------------------------
  /// Success Green — paid / settled orders.
  static const Color success = AppColors.success;

  static const Color successContainer = AppColors.successBg;

  /// Alert Amber — pending kitchen / attention states.
  static const Color alert = AppColors.warning;

  static ThemeData light() => _build();

  /// Dark is intentionally aliased to light — the app is light-only.
  /// Even if [ThemeProvider] receives `ThemeMode.dark` (or system resolves
  /// to dark), the light color combo is returned so there is never a
  /// dark/light conflict. Keep this alias until a full dark palette is
  /// designed for every hardcoded `AppColors.ink / Colors.white` widget.
  static ThemeData dark() => _build();

  /// Spec §4 — Poppins text scale (w800 display → w700 labels).
  static TextTheme get baseTextTheme => const TextTheme(
        displaySmall: TextStyle(fontSize: 30, fontWeight: FontWeight.w800),
        headlineMedium: TextStyle(fontSize: 24, fontWeight: FontWeight.w800),
        headlineSmall: TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
        titleLarge: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
        titleMedium: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
        titleSmall: TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
        bodyLarge: TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
        bodyMedium: TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
        bodySmall: TextStyle(fontSize: 12, fontWeight: FontWeight.w500),
        labelLarge: TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
        labelMedium: TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
      );

  static ThemeData _build() {
    // Light-only: always builds the light combo so dark can never leak a
    // charcoal palette under widgets hardcoded for light
    // (`Colors.white` cards, `AppColors.ink` text, page gradient…).
    final scheme = ColorScheme.fromSeed(seedColor: AppColors.primary).copyWith(
      brightness: Brightness.light,
      primary: AppColors.primary,
      onPrimary: Colors.white,
      primaryContainer: AppColors.infoBg,
      onPrimaryContainer: AppColors.ink,
      secondary: AppColors.primarySoft,
      onSecondary: Colors.white,
      secondaryContainer: AppColors.infoBg,
      onSecondaryContainer: AppColors.ink,
      surface: AppColors.surface,
      onSurface: AppColors.ink,
      onSurfaceVariant: AppColors.muted,
      surfaceContainerLowest: Colors.white,
      surfaceContainerLow: AppColors.surfaceSoft2,
      surfaceContainer: AppColors.surfaceMuted,
      surfaceContainerHigh: AppColors.surfaceSubtle,
      outlineVariant: AppColors.border,
      outline: AppColors.borderMuted,
      error: AppColors.error,
      onError: Colors.white,
      errorContainer: AppColors.errorBg,
      onErrorContainer: AppColors.error,
      tertiary: AppColors.accentTeal,
      tertiaryContainer: AppColors.infoBg,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      scaffoldBackgroundColor: AppColors.background,
      splashFactory: InkSparkle.splashFactory,
      textTheme: GoogleFonts.poppinsTextTheme(baseTextTheme),
      // Spec §6 — AppBar transparent, 0 elevation, ink fg + gradient space.
      appBarTheme: const AppBarTheme(
        backgroundColor: Colors.transparent,
        foregroundColor: AppColors.ink,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.w700,
          color: AppColors.ink,
          letterSpacing: -0.2,
        ),
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.lg),
          side: const BorderSide(color: AppColors.border),
        ),
      ),
      // Spec §6 — buttons 18/14 pad, radius 12, w700.
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(48, 52),
          padding:
              const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppRadius.md)),
          textStyle:
              const TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size(48, 52),
          padding:
              const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppRadius.md)),
          side: const BorderSide(color: AppColors.primary),
          textStyle:
              const TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          padding:
              const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppRadius.md)),
          textStyle:
              const TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
        ),
      ),
      // Spec §6 — inputs: filled white, 14/14 pad, radius 12, muted label.
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        hintStyle:
            const TextStyle(color: AppColors.muted, fontSize: 14),
        labelStyle: const TextStyle(
            color: AppColors.muted, fontWeight: FontWeight.w600, fontSize: 14),
        floatingLabelStyle: const TextStyle(
            color: AppColors.primary, fontWeight: FontWeight.w700),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(
              color: AppColors.border.withValues(alpha: 0.6)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide: BorderSide(
              color: AppColors.border.withValues(alpha: 0.6)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide:
              const BorderSide(color: AppColors.primary, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppRadius.md),
          borderSide:
              const BorderSide(color: AppColors.error, width: 1.5),
        ),
      ),
      // Spec §6 — chips pill, white bg, selected infoBg.
      chipTheme: ChipThemeData(
        shape: const StadiumBorder(
            side: BorderSide(color: AppColors.border)),
        backgroundColor: Colors.white,
        selectedColor: AppColors.infoBg,
        labelStyle: const TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: AppColors.muted),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      ),
      segmentedButtonTheme: SegmentedButtonThemeData(
        style: SegmentedButton.styleFrom(
          visualDensity: VisualDensity.compact,
          backgroundColor: Colors.white,
          foregroundColor: AppColors.muted,
          selectedForegroundColor: Colors.white,
          selectedBackgroundColor: AppColors.primary,
          side: const BorderSide(color: AppColors.border),
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(AppRadius.md)),
          textStyle:
              const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700),
        ),
      ),
      // Spec §6 — snackbar floating, radius 12, ink bg / white text.
      snackBarTheme: const SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: AppColors.ink,
        contentTextStyle: TextStyle(color: Colors.white),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.all(Radius.circular(AppRadius.md)),
        ),
      ),
      // Spec §6 — bottomNav fixed, 0 elev, selected primary w700.
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        type: BottomNavigationBarType.fixed,
        elevation: 0,
        backgroundColor: Colors.white,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.muted,
        selectedLabelStyle:
            TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
        unselectedLabelStyle:
            TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
      ),
      tabBarTheme: const TabBarThemeData(
        labelColor: AppColors.primary,
        unselectedLabelColor: AppColors.muted,
        labelStyle: TextStyle(fontWeight: FontWeight.w700),
        unselectedLabelStyle: TextStyle(fontWeight: FontWeight.w600),
      ),
      dividerTheme: const DividerThemeData(
          color: AppColors.border, thickness: 1, space: 1),
      listTileTheme: const ListTileThemeData(
        iconColor: AppColors.primary,
        textColor: AppColors.ink,
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppRadius.lg))),
      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: Colors.white,
        showDragHandle: true,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
        ),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
          backgroundColor: AppColors.primary, foregroundColor: Colors.white),
      checkboxTheme: CheckboxThemeData(
        fillColor: WidgetStateProperty.resolveWith((s) =>
            s.contains(WidgetState.selected) ? AppColors.primary : null),
      ),
      radioTheme: RadioThemeData(
        fillColor: WidgetStateProperty.resolveWith((s) =>
            s.contains(WidgetState.selected) ? AppColors.primary : null),
      ),
      switchTheme: SwitchThemeData(
        thumbColor: WidgetStateProperty.resolveWith((s) =>
            s.contains(WidgetState.selected) ? AppColors.primary : null),
        trackColor: WidgetStateProperty.resolveWith((s) =>
            s.contains(WidgetState.selected)
                ? AppColors.primary.withValues(alpha: 0.4)
                : null),
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

/// Spec S2 — ThemeMode state + persist. Light-only: every setter normalises
/// to [ThemeMode.light] so even an old persisted 'dark' value (or system
/// dark) resolves to the same light color combo — no dark/light conflict.
class ThemeProvider extends ChangeNotifier {
  // ignore: prefer_initializing_formals
  ThemeProvider({SharedPreferences? prefs}) : _prefs = prefs {
    _load();
  }
  static const String storeKey = 'app_theme_mode';
  final SharedPreferences? _prefs;
  ThemeMode _mode = ThemeMode.light;
  ThemeMode get mode => _mode;
  Future<void> _load() async {
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      // Any stored value (dark/system/legacy) maps to light: single combo.
      _mode = ThemeMode.light;
      await p.setString(storeKey, 'light');
      notifyListeners();
    } catch (_) {}
  }

  Future<void> setMode(ThemeMode mode) async {
    // Intentionally ignores `mode` — light-only app, see class doc.
    _mode = ThemeMode.light;
    notifyListeners();
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.setString(storeKey, 'light');
    } catch (_) {}
  }

  Future<void> toggleTheme() async {
    await setMode(ThemeMode.light);
  }
}

/// Spec S7 - app chrome: transparent bar + page gradient, 44px round
/// back/menu button, brand pill (logo 34 + title 16/w700 + sub 11/muted).
class BizAppBar extends StatelessWidget implements PreferredSizeWidget {
  const BizAppBar(
      {super.key, this.title = '', this.subtitle = '', this.actions = const []});
  final String title;
  final String subtitle;
  final List<Widget> actions;
  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight + 8);
  @override
  Widget build(BuildContext context) {
    final canPop = Navigator.of(context).canPop();
    return AppBar(
      flexibleSpace: Container(
          decoration: const BoxDecoration(gradient: AppGradients.page)),
      leadingWidth: 60,
      leading: Padding(
        padding: const EdgeInsets.only(left: AppSpacing.lg),
        child: Center(
          child: SizedBox(
            width: 44,
            height: 44,
            child: Material(
              color: AppColors.ink,
              shape: const CircleBorder(),
              child: InkWell(
                customBorder: const CircleBorder(),
                onTap: () => canPop
                    ? Navigator.of(context).maybePop()
                    : Scaffold.of(context).openDrawer(),
                child: Icon(
                    canPop ? Icons.arrow_back_rounded : Icons.menu_rounded,
                    color: Colors.white,
                    size: 20),
              ),
            ),
          ),
        ),
      ),
      title: Flexible(
        child: Container(
          padding: const EdgeInsets.symmetric(
              horizontal: AppSpacing.md, vertical: 6),
          decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(AppRadius.full),
              border: Border.all(color: AppColors.border),
              boxShadow: AppShadows.card),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Container(
              width: 34,
              height: 34,
              decoration: const BoxDecoration(
                  gradient: AppGradients.brandMain, shape: BoxShape.circle),
              child: const Icon(Icons.storefront_rounded,
                  color: Colors.white, size: 18),
            ),
            const SizedBox(width: AppSpacing.sm),
            // Constrained-column text: a long store name shrinks to the
            // available app-bar width instead of overflowing it (no
            // black/yellow overflow stripes ever again).
            ConstrainedBox(
              constraints: const BoxConstraints(
                  maxWidth: 220, minWidth: 0),
              child: SizedBox(
                width: double.infinity,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(title.isEmpty ? 'BizBite' : title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: AppColors.ink)),
                    Text(subtitle.isEmpty ? 'Pay Desk' : subtitle,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w500,
                            color: AppColors.muted)),
                  ],
                ),
              ),
            ),
          ]),
        ),
      ),
      actions: actions,
    );
  }
}

/// Spec S8 - tinted finance tile (color 10pct bg, 22pct border, r12).
class FinanceCard extends StatelessWidget {
  const FinanceCard(
      {super.key,
      required this.label,
      required this.value,
      required this.icon,
      required this.color});
  final String label;
  final String value;
  final IconData icon;
  final Color color;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        border: Border.all(color: color.withValues(alpha: 0.22)),
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      child: Row(children: [
        Icon(icon, size: 18, color: color),
        const SizedBox(width: AppSpacing.sm),
        Expanded(
          child:
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(label,
                style:
                    theme.textTheme.bodySmall?.copyWith(color: AppColors.muted)),
            Text(value,
                style: theme.textTheme.titleMedium
                    ?.copyWith(fontWeight: FontWeight.w800)),
          ]),
        ),
      ]),
    );
  }
}

/// Spec S8 - status pill (success / warning / error tint).
class StatusPill extends StatelessWidget {
  const StatusPill({super.key, required this.label, required this.color});
  final String label;
  final Color color;
  Color get _bg {
    if (color == AppColors.success || color == AppColors.successDeep) {
      return AppColors.successBg;
    }
    if (color == AppColors.warning || color == AppColors.warningDeep) {
      return AppColors.warningBg;
    }
    return AppColors.errorBg;
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
          color: _bg, borderRadius: BorderRadius.circular(AppRadius.full)),
      child: Text(label,
          style: TextStyle(
              fontSize: 11, fontWeight: FontWeight.w700, color: color)),
    );
  }
}

/// Spec S7 - centered brand header used on auth screens.
class AuthBrandHeader extends StatelessWidget {
  const AuthBrandHeader(
      {super.key, this.title = 'BizBite', this.subtitle = 'Pay Desk'});
  final String title;
  final String subtitle;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(children: [
      Container(
        height: 64,
        width: 64,
        decoration: const BoxDecoration(
            gradient: AppGradients.brandMain, shape: BoxShape.circle),
        child: const Icon(Icons.storefront_rounded,
            color: Colors.white, size: 30),
      ),
      const SizedBox(height: AppSpacing.md),
      Text(title,
          style: theme.textTheme.headlineSmall
              ?.copyWith(fontWeight: FontWeight.w800, color: AppColors.ink)),
      Text(subtitle,
          style: theme.textTheme.bodyMedium?.copyWith(color: AppColors.muted)),
    ]);
  }
}

/// Spec S8 - white radius-16 search field card.
class SearchFieldCard extends StatelessWidget {
  const SearchFieldCard(
      {super.key,
      required this.controller,
      this.onChanged,
      this.hint = 'Search...'});
  final TextEditingController controller;
  final ValueChanged<String>? onChanged;
  final String hint;
  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(AppRadius.lg),
          boxShadow: AppShadows.card),
      child: TextField(
        controller: controller,
        onChanged: onChanged,
        textInputAction: TextInputAction.search,
        decoration: InputDecoration(
          prefixIcon: const Icon(Icons.search_rounded, size: 20),
          hintText: hint,
          border: InputBorder.none,
          enabledBorder: InputBorder.none,
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppRadius.lg),
            borderSide:
                const BorderSide(color: AppColors.primary, width: 1.5),
          ),
        ),
      ),
    );
  }
}