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
- **Brand:** `primary #4F46E5` (indigo seed), `accentBlue #2F6FED`, `primaryLight #3366FF`, `primarySoft #2563EB`, `skyBlue #00A3FF`, `purple #8A4FDB`, `infoBg #F0F4FF`.
- **Text:** `ink #14151F`, `inkDark #0F172A`, `muted #9AA1AC`, `faintMuted #B8BEC9`, slate scale (`#334155`, `#475569`, `#64748B`, `#94A3B8`, `#1E293B`).
- **Surfaces:** `background #F7F8FB`, `surface #FFFFFF`, `surfaceMuted #F3F4F6`, `surfaceSoft #F8F9FA`, `surfaceSoft2 #F5F6FA`, `surfaceSubtle #F0F1F4`.
- **Gradients:** page `gradientStart #F4F7FC → gradientMid #E9EDF5 → gradientEnd #E3E8F3`; brand dark `ink → darkSurface #2A2D3A`.
- **Borders:** `border #E2E5EA`, `borderLight #E2E8F0`, `borderMuted #CBD5E1`.
- **Status:** success `#34A853/#22C55E/#16A34A`, warning `#FF8C42/#FF6D00`, error `#D92D20/#EF4444/#DC2626`, `infoCyan #17A8C4`, `warningBg #FEF9C3`, `whatsapp #25D366`.
- **Dark mode:** bg `#14151F`, cards `#1E222B`, primary `#818CF8`, accents `#93C5FD`.

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

