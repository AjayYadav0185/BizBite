# BizBite

A new Flutter project.

## Getting Started

This project is a starting point for a Flutter application.

A few resources to get you started if this is your first Flutter project:

- [Learn Flutter](https://docs.flutter.dev/get-started/learn-flutter)
- [Write your first Flutter app](https://docs.flutter.dev/get-started/codelab)
- [Flutter learning resources](https://docs.flutter.dev/reference/learning-resources)

For help getting started with Flutter development, view the
[online documentation](https://docs.flutter.dev/), which offers tutorials,
samples, guidance on mobile development, and a full API reference.

## Brand assets (logo, launcher icons, splash)

Every BizBite mark is derived from a single master file —
`assets/icons/bizbite_logo.png`:

| Output | Location |
| --- | --- |
| In-app badge (app bar, drawer, splash, auth header) | `assets/icons/bizbite_mark.png` — bundled, referenced by `AppBrandAssets` / `BizBiteLogoMark` in `lib/presentation/theme/bizbite_theme.dart` |
| Android launcher icon (legacy + round + adaptive) | `android/app/src/main/res/mipmap-*` |
| Android native splash | `android/app/src/main/res/drawable-*/launch_logo.png` + `drawable/launch_background.xml` (backdrop colour: `values[-night]/colors.xml`) |
| iOS app icon | `ios/Runner/Assets.xcassets/AppIcon.appiconset/` |
| iOS launch image | `ios/Runner/Assets.xcassets/LaunchImage.imageset/` |
| Web logo + favicons | `../public/images/*` and `../public/favicon.ico` (Laravel portals) |

Regenerate all of them after changing the master artwork:

```bash
bash mobile/scripts/generate_brand_icons.sh   # requires ImageMagick 7 (`magick`)
```

The generated PNGs are committed, so treat them as build output: never
hand-edit them, re-run the script instead.
