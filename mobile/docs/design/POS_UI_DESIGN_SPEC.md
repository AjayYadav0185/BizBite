# BizaroHQ Flutter App — UI & Theme Design

Short reference for the UI system only (colors, theme, components, patterns).

## 1. Overview
- **App:** `bizaro_hq` — Garage Management Admin App.
- **Framework:** Flutter + Material 3 (`useMaterial3: true`), `MaterialApp` with `theme` / `darkTheme` / `themeMode`.
- **Entry:** `lib/main.dart` → `MyApp` (MultiProvider + `Consumer<ThemeProvider>`).
- **Theme switch:** `ThemeProvider` (`light / dark / system`, persisted in `SharedPreferences` key `app_theme_mode`).

## 2. Theme Architecture (single source of truth)
| File | Role |
|---|---|
| `lib/app/theme/app_colors.dart` | **All colors.** Never hardcode hex elsewhere. |
| `lib/app/theme/app_theme.dart` | **All ThemeData.** `lightTheme()` / `darkTheme()`, `baseTextTheme`, `AppSpacing`, `AppRadius`, `AppShadows`, `AppGradients`. |
| `lib/app/providers/theme_provider.dart` | ThemeMode state + persist + `toggleTheme()` (light → dark → system). |
| `lib/main.dart` | Applies `GoogleFonts.poppinsTextTheme()` over both themes. |

> To re-skin the app edit only `app_colors.dart` + `app_theme.dart`.

## 3. Color Tokens (`AppColors`)
> Palette mirrors the BizBite **web POS console**: food-outlet emerald green
> over charcoal slate, warm amber warnings, red errors. Editing this section
> in `bizbite_theme.dart` re-skins the whole app.
- **Brand (emerald — web POS `emerald-500` CTA):** `primary #10B981`, `primaryDark #34D399` (dark mode), `primaryLight #34D399`, `primarySoft #059669` (emerald-600), `accentTeal #14B8A6`, `skyTeal #0D9488`, `primaryDeep #047857`, `infoBg #ECFDF5` (emerald-50 wash), `accentSoft #6EE7B7` (emerald-300), `purple #8A4FDB`.
- **Text (web slate scale):** `ink #0F172A` (slate-900), `inkDark #020617` (slate-950), `muted #64748B` (slate-500), `faintMuted #94A3B8` (slate-400), slate scale (`#334155`, `#475569`, `#64748B`, `#94A3B8`, `#1E293B`).
- **Surfaces:** `background #F5F7F6` (green-white), `surface #FFFFFF`, `surfaceMuted #F0F3F1`, `surfaceSoft #F8FAF8`, `surfaceSoft2 #F1F4F2`, `surfaceSubtle #EBEFED`.
- **Gradients:** page `gradientStart #F2F6F4 → gradientMid #E6EDE8 → gradientEnd #DCE5DF`; brand emerald `primary #10B981 → primarySoft #059669`; brand charcoal `ink #0F172A → darkSurface #1E293B`.
- **Borders:** `border #E1E7E3`, `borderLight #E2E8E4`, `borderMuted #CBD5E1`.
- **Status:** success (emerald "paid") `#10B981/#059669/#34A853`, successBg `#ECFDF5`; warning `#FF8C42/#FF6D00`, warningBg `#FFF3E8`; error (web red) `#EF4444/#F87171/#DC2626`, errorBg `#FEF2F2`; `infoCyan #17A8C4`, `whatsapp #25D366`.
- **Dark mode (charcoal, mirrors web slate-950/900/800):** bg `#020617`, cards `#0F172A`, darkSurface `#1E293B`, primary `#34D399`, accents `#6EE7B7`. On-emerald foreground is `inkDark #020617` (dark text on the emerald CTA, exactly like the web console).

## 4. Typography
- **Font:** Poppins everywhere — `google_fonts` (`poppinsTextTheme()` in `main.dart`) + bundled `assets/fonts/Poppins-Regular/SemiBold/Bold.ttf`.
- **Scale (`baseTextTheme`):** `displaySmall 30/w800`, `headlineMedium 24/w800`, `headlineSmall 20/w700`, `titleLarge 18/w700`, `titleMedium 16/w700`, `titleSmall 14/w600`, `bodyLarge 16/w500`, `bodyMedium 14/w500`, `bodySmall 12/w500`, `labelLarge 14/w700`, `labelMedium 12/w700`.
- **Usage:** headings `ink` w700/w800, body `ink`, secondary/hint `muted`.

## 5. Spacing / Radius / Shadows / Gradients
- **Spacing (`AppSpacing`):** `xs 4, sm 8, md 12, lg 16, xl 24, xxl 32`.
- **Radius (`AppRadius`):** `sm 8, md 12, lg 16, full 999`. Cards 16–18, inputs 12, chips pill.
- **Shadow (`AppShadows.card`):** `0,2 / blur 12 / #14151F @5%`. Elevation `0` — shadows do depth.
- **Gradients (`AppGradients`):** `page` (app-bars, backdrops); `brand` (drawer header, home hero, dark tiles).

## 6. Core Components (themed in `AppTheme`)
- **Scaffold:** `background` (light) / ink (dark).
- **AppBar:** transparent, `0` elevation, `ink` foreground, `centerTitle: false` + gradient `flexibleSpace` on real screens.
- **Cards:** white, `radius 16`, `elevation 0` + soft shadow.
- **Inputs:** filled white, `14/14` padding, `radius 12`, border `border @60%`, focus `primary 1.5`, error `error 1.5`, label `muted w600`, floating `primary w700`.
- **Buttons:** Filled `primary`/white; Outlined `primary` + `border`; Text `primary`; all `18/14` padding, `radius 12`, `w700`.
- **Chips:** pill, white bg, selected `infoBg`, label `muted 12/w700`.
- **Dialogs/BottomSheets:** white (`#14151F` dark), `radius 16`, no surface tint.
- **Snackbar:** floating, `radius 12`, `ink` bg / white text.
- **BottomNav/Tabs:** fixed, `0` elevation, selected `primary` w700, unselected `muted` w600.
- **Lists/Dividers/FAB:** `ListTile ink`, `Divider border 1px`, FAB `primary`/white. Checkbox/Radio/Switch `primary` when selected.

## 7. App Chrome (custom)
- **`AppAppBar`:** transparent + page gradient, circular `ink` back/menu button (44px), brand pill (logo 34px + title 16/w700 + subtitle 11/muted), bell with badge + profile button.
- **Bottom nav (`MainScreen`):** custom white bar + top shadow, 5 items (Home/Customers/Vehicles/JobCards/Inventory); active = `ink` circle + white icon, label `9.5px`.
- **Drawer:** brand gradient header + section titles (11/w700/muted) + rows (36px `surfaceMuted` circle icon + 13.5/w600 + chevron).
- **Auth header (`AuthBrandHeader`):** centered `assets/images/bizarohq.png` (h 64) + name `22/w800/ink`.

## 8. Reusable Widgets
- **`SearchSection<T>`:** white `radius 16` search field + dropdown results card (maxH 200) + removable `Chip`s.
- **`FinanceCard` / `FinanceCompareCard` / `MiniBar`:** tinted container (`color @10%` bg, `@22%` border, `radius 12`), 18px icon + 10px label + 14/w800 value.
- **`FloatingChatbotWidget`:** global FAB chatbot overlay. **`CarInspection3DWidget`:** WebView 3D inspection. **`VehicleDamageSvgPicker`:** SVG painter with vehicle palette tokens.

## 9. Screen Patterns
- **Auth:** light bg, centered brand header, white card form, filled primary CTA + text links.
- **Dashboard/Home:** gradient header + dark brand hero, 2-col `FinanceCard` grid, white `radius 18` panels, quick-action tiles (`color @8%`, `radius 14`).
- **Lists:** `AppAppBar` + search card + filter chips + white rounded rows, status pill (success/warning/error), FAB for add.
- **Forms:** grouped white sections, themed fields, `SearchSection` pickers, sticky filled save button.
- **Kanban/Analytics/Reports/Reminders/Stock/Insurance/Loyalty/Downloads/Notifications/Profile:** same card language — white panels, muted labels, pill chips, primary actions; charts use brand + status colors.

## 10. Assets / Icons / i18n
- **Assets:** `assets/images/bizarohq.png`, `assets/icons/bizarohq.png`, `assets/animations/`, `assets/fonts/Poppins-*`.
- **Icons:** Material (primary) + `cupertino_icons` + `font_awesome_flutter`.
- **i18n:** `l10n/` (en/hi/bn/gu/kn/mr/ta/te/ar) via `AppLocalizations`, RTL-ready.

