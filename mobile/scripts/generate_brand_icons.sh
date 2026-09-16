#!/usr/bin/env bash
#
# =============================================================================
# BizBite — brand asset generator
# =============================================================================
# Derives EVERY branding asset from the single master artwork
#
#     mobile/assets/icons/bizbite_logo.png      (1408x768 master, transparent)
#
# so the Android launcher icon, the iOS app icon, the native launch screens
# (Android launch_background + iOS LaunchScreen) and the Laravel web portals
# all render the *same* BizBite mark:
#
#   mobile/assets/icons/bizbite_mark.png      <- round badge, tight, transparent
#   mobile/assets/icons/bizbite_app_icon.png  <- 1024 square, full-bleed tile
#   mobile/android/app/src/main/res/mipmap-*  <- legacy + round launcher icons
#   mobile/android/app/src/main/res/mipmap-*/ic_launcher_foreground.png (adaptive)
#   mobile/android/app/src/main/res/drawable/launch_background.xml (splash)
#   mobile/android/app/src/main/res/values[-night]/colors.xml (splash backdrop)
#   mobile/ios/Runner/Assets.xcassets/AppIcon.appiconset/*.png
#   mobile/ios/Runner/Assets.xcassets/LaunchImage.imageset/LaunchImage*.png
#   public/images/bizbite_*.png + public/favicon.ico   (Laravel portals)
#
# Usage:   bash mobile/scripts/generate_brand_icons.sh
# Needs:   ImageMagick 7 (`magick`)   ->  brew install imagemagick
#
# Re-run this whenever the master artwork changes — never hand-edit the
# generated PNGs.
# =============================================================================

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"   # .../BizBite/mobile
PROJECT="$(cd "$ROOT/.." && pwd)"                         # .../BizBite
SRC="$ROOT/assets/icons/bizbite_logo.png"                 # master artwork
MARK="$ROOT/assets/icons/bizbite_mark.png"                # generated: badge
APPICON="$ROOT/assets/icons/bizbite_app_icon.png"         # generated: 1024 tile
ANDROID="$ROOT/android/app/src/main/res"
IOS="$ROOT/ios/Runner/Assets.xcassets"
WEB="$PROJECT/public/images"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

command -v magick >/dev/null 2>&1 || {
  echo "error: ImageMagick 7 ('magick') not found — brew install imagemagick" >&2
  exit 1
}
[ -f "$SRC" ] || { echo "error: master artwork missing: $SRC" >&2; exit 1; }

# Tile backdrop: the badge's OWN vertical gradient, sampled from the artwork
# (#3A2D56 at the circle's top edge -> #221738 at its bottom edge). Painting the
# square tile with it lets the round badge sit edge-to-edge without a seam, so
# the same artwork works as a full-bleed launcher icon.
BG_TOP="#3A2D56"
BG_BOTTOM="#221738"

# =============================================================================
# 1. Trim the master artwork down to the round badge
# =============================================================================
# The master is a 1408x768 canvas with the badge floating in the middle and a
# lot of transparent margin; using it as-is would render the mark tiny. Trim to
# the visible pixels, then cut a square around that bounding box so the badge is
# perfectly centred (important: the "BizBite" wordmark spans nearly the full
# width of the circle, so the square must not be any tighter than the badge).
bb="$(magick "$SRC" -background none -alpha set -trim -format '%wx%h%O' info:)"
bw="${bb%%x*}"; rest="${bb#*x}"
bh="${rest%%+*}"; rest="${rest#*+}"
bx="${rest%%+*}"; by="${rest##*+}"

side=$(( bw > bh ? bw : bh ))
cx=$(( bx + bw / 2 )); cy=$(( by + bh / 2 ))
sx=$(( cx - side / 2 )); sy=$(( cy - side / 2 ))
if [ "$sx" -lt 0 ]; then sx=0; fi
if [ "$sy" -lt 0 ]; then sy=0; fi

echo "master badge bbox ${bb} -> square ${side}x${side}+${sx}+${sy}"

magick "$SRC" -crop "${side}x${side}+${sx}+${sy}" +repage \
  -resize 512x512 -strip "$MARK"

# =============================================================================
# 2. 1024 square tile (the source for every launcher / store icon)
# =============================================================================
magick -size 1024x1024 "gradient:${BG_TOP}-${BG_BOTTOM}" -strip "$TMP/bg1024.png"
magick "$MARK" -resize 1024x1024! -strip "$TMP/mark1024.png"
# Flatten onto the gradient and drop alpha: iOS App Store icons must be opaque.
magick "$TMP/bg1024.png" "$TMP/mark1024.png" -geometry +0+0 -composite \
  -alpha off -strip -define png:color-type=2 "$APPICON"
echo "wrote $MARK"
echo "wrote $APPICON"

# =============================================================================
# 3. Android launcher icons
# =============================================================================
# a) Legacy density-bucket icons (API < 26) — square tile + round variant.
declare -a LEGACY=("mdpi:48" "hdpi:72" "xhdpi:96" "xxhdpi:144" "xxxhdpi:192")
for entry in "${LEGACY[@]}"; do
  name="${entry%%:*}"; px="${entry##*:}"
  dir="$ANDROID/mipmap-$name"
  mkdir -p "$dir"
  magick "$APPICON" -resize "${px}x${px}" -alpha off -strip \
    -define png:color-type=2 "$dir/ic_launcher.png"
  # Round icon: same tile clipped to a circle for launchers that request one.
  magick "$APPICON" -resize "${px}x${px}" \
    \( -size "${px}x${px}" xc:none -fill white \
       -draw "circle $(( px / 2 )),$(( px / 2 )) $(( px / 2 )),0" \) \
    -alpha off -compose CopyOpacity -composite -strip \
    -define png:color-type=6 "$dir/ic_launcher_round.png"
done
echo "android: legacy ic_launcher(.round) x${#LEGACY[@]} densities"

# b) Adaptive icon foreground (API 26+). The system crops an adaptive icon to
#    whatever shape the launcher uses, so the badge must stay inside the inner
#    66dp of the 108dp canvas (=61%) or the wordmark gets clipped.
declare -a ADAPTIVE=("mdpi:108" "hdpi:162" "xhdpi:216" "xxhdpi:324" "xxxhdpi:432")
for entry in "${ADAPTIVE[@]}"; do
  name="${entry%%:*}"; px="${entry##*:}"
  inner=$(( px * 62 / 100 ))
  magick "$MARK" -resize "${inner}x${inner}" -background none \
    -gravity center -extent "${px}x${px}" -strip \
    "$ANDROID/mipmap-$name/ic_launcher_foreground.png"
done
mkdir -p "$ANDROID/mipmap-anydpi-v26" "$ANDROID/drawable"
cat > "$ANDROID/drawable/ic_launcher_background.xml" <<'XML'
<?xml version="1.0" encoding="utf-8"?>
<!-- BizBite adaptive-icon backdrop: the badge's own vertical gradient
     (#3A2D56 -> #221738), matching public/images/bizbite_app_icon.png. -->
<shape xmlns:android="http://schemas.android.com/apk/res/android"
    android:shape="rectangle">
    <gradient
        android:angle="270"
        android:endColor="#221738"
        android:startColor="#3A2D56"
        android:type="linear" />
</shape>
XML
for shape in ic_launcher ic_launcher_round; do
  cat > "$ANDROID/mipmap-anydpi-v26/$shape.xml" <<'XML'
<?xml version="1.0" encoding="utf-8"?>
<!-- BizBite launcher icon (API 26+). Generated by
     mobile/scripts/generate_brand_icons.sh — do not hand-edit. -->
<adaptive-icon xmlns:android="http://schemas.android.com/apk/res/android">
    <background android:drawable="@drawable/ic_launcher_background" />
    <foreground android:drawable="@mipmap/ic_launcher_foreground" />
</adaptive-icon>
XML
done
# =============================================================================
# 4. Android native launch screen (the window shown before Flutter draws)
# =============================================================================
# A density-aware logo bitmap + the layer-list the LaunchTheme windowBackground
# points at (values/styles.xml and values-night/styles.xml).
#
# The backdrop is deliberately a COLOUR RESOURCE and not a literal hex:
# `android:drawable` inside a <layer-list> requires a reference, and splitting
# it across values/colors.xml (light) + values-night/colors.xml (dark) lets a
# single drawable serve both UI modes — no drawable-v21/ or drawable-night/
# override is needed. The colours are hand-maintained (see values/colors.xml)
# because they must track AppColors.gradientStart in bizbite_theme.dart.
declare -a DRAWABLE=("mdpi:96" "hdpi:144" "xhdpi:192" "xxhdpi:288" "xxxhdpi:384")
for entry in "${DRAWABLE[@]}"; do
  name="${entry%%:*}"; px="${entry##*:}"
  mkdir -p "$ANDROID/drawable-$name"
  magick "$MARK" -resize "${px}x${px}" -strip "$ANDROID/drawable-$name/launch_logo.png"
done

cat > "$ANDROID/drawable/launch_background.xml" <<'XML'
<?xml version="1.0" encoding="utf-8"?>
<!-- BizBite launch screen (the window Android shows before Flutter paints).
     Generated by mobile/scripts/generate_brand_icons.sh — do not hand-edit.
     The backdrop colour resolves per UI mode from @color/bizbite_launch_background
     (values/ = light, values-night/ = dark), so one drawable covers both. -->
<layer-list xmlns:android="http://schemas.android.com/apk/res/android">
    <item android:drawable="@color/bizbite_launch_background" />
    <item>
        <bitmap
            android:gravity="center"
            android:src="@drawable/launch_logo" />
    </item>
</layer-list>
XML
# Belt and braces: a missing colour resource fails the AAPT link step with a
# cryptic message, so shout about it here instead.
for f in values/colors.xml values-night/colors.xml; do
  [ -f "$ANDROID/$f" ] || echo "warn: $ANDROID/$f missing — launch backdrop will not resolve"
done
echo "android: launch_background + launch_logo x${#DRAWABLE[@]} densities"
# =============================================================================
# 5. iOS app icon + launch image
# =============================================================================
# Every slot declared in AppIcon.appiconset/Contents.json. iOS icons must be
# fully opaque (no alpha channel) or the App Store build is rejected.
cd "$IOS/AppIcon.appiconset"
declare -a IOS_ICONS=(
  "Icon-App-20x20@1x.png:20"   "Icon-App-20x20@2x.png:40"   "Icon-App-20x20@3x.png:60"
  "Icon-App-29x29@1x.png:29"   "Icon-App-29x29@2x.png:58"   "Icon-App-29x29@3x.png:87"
  "Icon-App-40x40@1x.png:40"   "Icon-App-40x40@2x.png:80"   "Icon-App-40x40@3x.png:120"
  "Icon-App-60x60@2x.png:120"  "Icon-App-60x60@3x.png:180"
  "Icon-App-76x76@1x.png:76"   "Icon-App-76x76@2x.png:152"
  "Icon-App-83.5x83.5@2x.png:167"
  "Icon-App-1024x1024@1x.png:1024"
)
for entry in "${IOS_ICONS[@]}"; do
  file="${entry%%:*}"; px="${entry##*:}"
  magick "$APPICON" -resize "${px}x${px}" -alpha off -strip \
    -define png:color-type=2 "$file"
done
echo "ios: AppIcon.appiconset x${#IOS_ICONS[@]} slots"

# LaunchScreen.storyboard centres LaunchImage at its intrinsic size (no
# width/height constraints), so the 1x bitmap IS the on-screen point size:
# 144pt (1x) / 288px (2x) / 432px (3x). Keep the <resources> size in the
# storyboard in sync with the 1x asset (LAUNCH_PT below).
LAUNCH_PT=144
cd "$IOS/LaunchImage.imageset"
magick "$MARK" -resize "${LAUNCH_PT}x${LAUNCH_PT}" -strip LaunchImage.png
magick "$MARK" -resize "$(( LAUNCH_PT * 2 ))x$(( LAUNCH_PT * 2 ))" -strip LaunchImage@2x.png
magick "$MARK" -resize "$(( LAUNCH_PT * 3 ))x$(( LAUNCH_PT * 3 ))" -strip LaunchImage@3x.png
echo "ios: LaunchImage ${LAUNCH_PT}pt (1x/2x/3x)"
# =============================================================================
# 6. Laravel web assets (login page, admin/pos portal chrome, browser tab)
# =============================================================================
mkdir -p "$WEB"
# Master + tight mark (transparent) for <img> tags in the Blade layouts.
magick "$SRC" -strip "$WEB/bizbite_logo.png"
magick "$MARK" -strip "$WEB/bizbite_logo_mark.png"
# Opaque square tile for touch icons / PWA / social cards.
magick "$APPICON" -resize 512x512 -alpha off -strip \
  -define png:color-type=2 "$WEB/bizbite_app_icon.png"
magick "$APPICON" -resize 192x192 -alpha off -strip \
  -define png:color-type=2 "$WEB/android-chrome-192x192.png"
magick "$APPICON" -resize 180x180 -alpha off -strip \
  -define png:color-type=2 "$WEB/apple-touch-icon.png"
magick "$APPICON" -resize 32x32 -alpha off -strip \
  -define png:color-type=2 "$WEB/favicon-32x32.png"
magick "$APPICON" -resize 16x16 -alpha off -strip \
  -define png:color-type=2 "$WEB/favicon-16x16.png"

# Multi-resolution /public/favicon.ico (16 + 32 + 48) replaces the Laravel
# default so the browser tab shows the BizBite badge.
magick "$APPICON" -resize 16x16 -alpha off "$TMP/fav16.png"
magick "$APPICON" -resize 32x32 -alpha off "$TMP/fav32.png"
magick "$APPICON" -resize 48x48 -alpha off "$TMP/fav48.png"
magick "$TMP/fav16.png" "$TMP/fav32.png" "$TMP/fav48.png" "$PROJECT/public/favicon.ico"
echo "web: public/images/* + public/favicon.ico"

echo ""
echo "BizBite brand assets regenerated from $(basename "$SRC")."
echo "Remember: the mobile UI loads assets/icons/bizbite_mark.png (see pubspec.yaml)."
echo "android: adaptive icon (anydpi-v26 + foreground x${#ADAPTIVE[@]} densities)"