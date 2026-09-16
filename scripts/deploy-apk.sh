#!/usr/bin/env bash
#
# Build the BizBite Android app and publish it to the Laravel /download-app
# distribution folder. The download URL never changes — dropping the new APK
# into storage/app/public/apks makes /download-app/download serve it instantly.
#
# Usage:
#   bash scripts/deploy-apk.sh                 # release build
#   bash scripts/deploy-apk.sh --debug         # debug build
#   bash scripts/deploy-apk.sh --build-name 1.2.3 --build-number 45
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MOBILE="$ROOT/mobile"
APK_DIR="$ROOT/storage/app/public/apks"

FLAVOR="release"
EXTRA_ARGS=()
while [[ $# -gt 0 ]]; do
    case "$1" in
        --debug)        FLAVOR="debug"; shift ;;
        --build-name)   EXTRA_ARGS+=(--build-name "$2"); shift 2 ;;
        --build-number) EXTRA_ARGS+=(--build-number "$2"); shift 2 ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

echo "==> Building Flutter APK ($FLAVOR)..."
(cd "$MOBILE" && flutter build apk --"$FLAVOR" "${EXTRA_ARGS[@]}")

SRC="$MOBILE/build/app/outputs/flutter-apk/app-$FLAVOR.apk"
[[ -f "$SRC" ]] || { echo "ERROR: build output not found at $SRC"; exit 1; }

# Version comes from pubspec.yaml (version: x.y.z+build), overridable via
# --build-name/--build-number which rewrite it during the build.
VERSION="$(grep -E '^version:' "$MOBILE/pubspec.yaml" | sed -E 's/^version:[[:space:]]*//; s/[[:space:]]*#.*$//')"
VER_NAME="${VERSION%%+*}"
VER_BUILD="${VERSION##*+}"
[[ "$VERSION" == *"+"* ]] || VER_BUILD="1"

mkdir -p "$APK_DIR"
DEST="$APK_DIR/bizbite-$VER_NAME+$VER_BUILD.apk"

echo "==> Publishing $SRC -> $DEST"
cp -f "$SRC" "$DEST"

# Convenience: list what's live now.
LATEST="$("$ROOT/artisan tinker --execute='echo (\App\Http\Controllers\AppDownloadController::latestApk())["filename"] ?? "none";' 2>/dev/null || basename "$DEST")"
echo "==> Done. /download-app now serves: $LATEST"
