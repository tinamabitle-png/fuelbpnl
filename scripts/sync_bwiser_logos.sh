#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_URL="${BWISER_BASE_URL:-https://bwiser.co.za}"

cd "$ROOT_DIR"

if ! command -v curl >/dev/null 2>&1; then
  echo "curl is required." >&2
  exit 1
fi

tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

download_one() {
  local rel="$1"
  local dest="$2"

  mkdir -p "$(dirname "$dest")"

  local url="${BASE_URL%/}/${rel#/}"
  local tmp="$tmp_dir/$(basename "$dest").tmp"

  echo "GET $url"
  curl -fsSL "$url" -o "$tmp"

  # Only replace when content changed.
  if [[ -f "$dest" ]]; then
    if command -v shasum >/dev/null 2>&1; then
      local a b
      a="$(shasum -a 256 "$dest" | awk '{print $1}')"
      b="$(shasum -a 256 "$tmp" | awk '{print $1}')"
      if [[ "$a" == "$b" ]]; then
        echo "UNCHANGED $dest"
        rm -f "$tmp"
        return 0
      fi
    fi
  fi

  mv -f "$tmp" "$dest"
  echo "UPDATED $dest"
}

# Mobility logos as referenced by the live welcome page on the VPS.
download_one "/images/driver-platforms/uber.svg" "public/images/driver-platforms/uber.svg"
download_one "/images/driver-platforms/uber-eats.svg" "public/images/driver-platforms/uber-eats.svg"
download_one "/images/driver-platforms/indrive.png" "public/images/driver-platforms/indrive.png"
download_one "/images/driver-platforms/takealot.png" "public/images/driver-platforms/takealot.png"
download_one "/images/driver-platforms/mrd.png" "public/images/driver-platforms/mrd.png"
download_one "/images/driver-platforms/sixty60.png" "public/images/driver-platforms/sixty60.png"

# Card network logos used on welcome + mobile welcome.
download_one "/images/cards/visa.png" "public/images/cards/visa.png"
download_one "/images/cards/mastercard.png" "public/images/cards/mastercard.png"

echo "Done."
