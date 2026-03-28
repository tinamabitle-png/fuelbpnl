#!/usr/bin/env bash
set -euo pipefail

# Downloads partner logos into the Flutter app asset directory.
# Useful when placeholders/photos accidentally get committed.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="$ROOT_DIR/mobile_app/assets/images/partners"
FALLBACK_DIR="$ROOT_DIR/public/images/driver-platforms"

mkdir -p "$OUT_DIR"

fetch_png() {
  local label="$1"
  local url="$2"
  local dest="$3"
  local tmp
  tmp="$(mktemp)"

  echo "Downloading $label..."
  if ! curl -fsSL -A "Mozilla/5.0 (Bwiser logo fetch)" "$url" -o "$tmp"; then
    rm -f "$tmp"
    echo "Failed: $label ($url)"
    return 1
  fi

  if ! file "$tmp" | grep -qi "PNG image data"; then
    echo "Not a PNG for $label. Got: $(file "$tmp")"
    rm -f "$tmp"
    return 1
  fi

  mv "$tmp" "$dest"
  echo "Saved: $dest"
}

# Mr D + Sixty60 (these are the two that often get replaced by photos by mistake)
fetch_png "Mr D" "https://logo.clearbit.com/mrdfood.com?size=256" "$OUT_DIR/mrd.png" || true
fetch_png "Sixty60" "https://logo.clearbit.com/sixty60.co.za?size=256" "$OUT_DIR/sixty60.png" || true

# Fallback domains (in case clearbit does not have the exact brand domains)
if ! file "$OUT_DIR/mrd.png" 2>/dev/null | grep -qi "PNG image data"; then
  fetch_png "Mr D (fallback)" "https://logo.clearbit.com/mrd.co.za?size=256" "$OUT_DIR/mrd.png" || true
fi

if ! file "$OUT_DIR/sixty60.png" 2>/dev/null | grep -qi "PNG image data"; then
  fetch_png "Sixty60 (fallback)" "https://logo.clearbit.com/checkers.co.za?size=256" "$OUT_DIR/sixty60.png" || true
fi

# Local fallback: if the web assets already exist (public site), reuse them.
if ! file "$OUT_DIR/mrd.png" 2>/dev/null | grep -qi "PNG image data"; then
  if [[ -f "$FALLBACK_DIR/mrd.png" ]]; then
    cp -f "$FALLBACK_DIR/mrd.png" "$OUT_DIR/mrd.png"
    echo "Copied Mr D from $FALLBACK_DIR/mrd.png"
  fi
fi

if ! file "$OUT_DIR/sixty60.png" 2>/dev/null | grep -qi "PNG image data"; then
  if [[ -f "$FALLBACK_DIR/sixty60.png" ]]; then
    cp -f "$FALLBACK_DIR/sixty60.png" "$OUT_DIR/sixty60.png"
    echo "Copied Sixty60 from $FALLBACK_DIR/sixty60.png"
  fi
fi

echo "---"
echo "Resulting files:"
file "$OUT_DIR/mrd.png" "$OUT_DIR/sixty60.png" || true
