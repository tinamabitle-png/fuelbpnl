#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
WORK_DIR="$ROOT_DIR/codecanyon/build/work"
DIST_DIR="$ROOT_DIR/codecanyon/dist"
VERSION="${1:-1.0.0}"
APP_ZIP="Bwiser-Laravel-App-v${VERSION}.zip"
PACKAGE_ZIP="Bwiser-CodeCanyon-Package-v${VERSION}.zip"

rm -rf "$WORK_DIR"
mkdir -p "$WORK_DIR/Main-Files" "$DIST_DIR"

php "$ROOT_DIR/codecanyon/scripts/generate_preview_images.php"

rsync -a \
  --exclude='.git' \
  --exclude='.env' \
  --exclude='.DS_Store' \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='mobile_app' \
  --exclude='lan-proxy/node_modules' \
  --exclude='database/database.sqlite' \
  --exclude='bootstrap/cache/*.php' \
  --exclude='storage/backups' \
  --exclude='storage/app/public/mobile' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='codecanyon' \
  --exclude='metatrader_analysis_bot' \
  "$ROOT_DIR/" "$WORK_DIR/app-src/"

(
  cd "$WORK_DIR/app-src"
  zip -rq "$WORK_DIR/Main-Files/$APP_ZIP" .
)
rm -rf "$WORK_DIR/app-src"

mkdir -p "$WORK_DIR/Documentation" "$WORK_DIR/Preview-Images" "$WORK_DIR/Licensing"
cp -R "$ROOT_DIR/codecanyon/Documentation/." "$WORK_DIR/Documentation/"
cp -R "$ROOT_DIR/codecanyon/Preview-Images/." "$WORK_DIR/Preview-Images/"
cp -R "$ROOT_DIR/codecanyon/Licensing/." "$WORK_DIR/Licensing/"
cp "$ROOT_DIR/codecanyon/changelog.txt" "$WORK_DIR/changelog.txt"
cp "$ROOT_DIR/codecanyon/reviewer-notes.md" "$WORK_DIR/reviewer-notes.md"

(
  cd "$WORK_DIR"
  zip -rq "$DIST_DIR/$PACKAGE_ZIP" .
)

echo "Built package: $DIST_DIR/$PACKAGE_ZIP"
