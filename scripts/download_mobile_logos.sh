#!/usr/bin/env bash
set -euo pipefail

# Downloads brand/card partner logos into the Flutter assets folder.
# Source: Wikimedia Commons (open API). Logos are trademarks; use in-app only if you have rights to use them.
#
# Usage:
#   bash scripts/download_mobile_logos.sh

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

UA="BwiserImageFetcher/1.0 (+https://bwiser.co.za; support@bwiser.co.za)"

CARDS_DIR="mobile_app/assets/images/cards"
PARTNERS_DIR="mobile_app/assets/images/partners"
mkdir -p "$CARDS_DIR" "$PARTNERS_DIR"

download_logo() {
  local brand="$1"
  local query="$2"
  local dest="$3"

  if [[ -s "$dest" ]]; then
    echo "ok: $dest"
    return 0
  fi

  local q_enc
  q_enc="$(php -r 'echo rawurlencode($argv[1]);' "$query")"

  local api="https://commons.wikimedia.org/w/api.php?action=query&format=json&generator=search&gsrnamespace=6&gsrlimit=8&gsrsearch=${q_enc}&prop=imageinfo&iiprop=url&iiurlwidth=640"
  local json
  json="$(curl -s --fail --connect-timeout 10 --max-time 30 -H "User-Agent: ${UA}" "$api")"

  local url
  url="$(php -r '
    $brand = strtolower(trim($argv[1]));
    $j = json_decode(stream_get_contents(STDIN), true);
    $pages = (array)($j["query"]["pages"] ?? []);
    $items = [];
    foreach ($pages as $p) {
      $idx = (int)($p["index"] ?? 999999);
      $title = strtolower((string)($p["title"] ?? ""));
      $ii = (array)($p["imageinfo"][0] ?? []);
      $u = (string)($ii["thumburl"] ?? $ii["url"] ?? "");
      if ($u === "") continue;
      $score = 0;
      if (str_contains($title, $brand)) $score += 3;
      if (str_contains($title, "logo")) $score += 2;
      if (str_contains($title, "svg")) $score += 1;
      $items[] = [$score, $idx, $u];
    }
    usort($items, function($a,$b){
      if ($a[0] !== $b[0]) return $b[0] <=> $a[0];
      return $a[1] <=> $b[1];
    });
    echo $items[0][2] ?? "";
  ' "$brand" <<<"$json")"

  if [[ -z "${url:-}" ]]; then
    echo "error: no result for $brand ($query)" >&2
    return 1
  fi

  echo "downloading: $brand"
  curl -L --fail --connect-timeout 10 --max-time 45 -H "User-Agent: ${UA}" "$url" -o "$dest"
  sleep 2
}

# Card network logos
download_logo "visa" "Visa logo" "${CARDS_DIR}/visa.png"
download_logo "mastercard" "Mastercard logo" "${CARDS_DIR}/mastercard.png"

# Mobility partners (South Africa)
download_logo "uber" "Uber logo" "${PARTNERS_DIR}/uber.png"
download_logo "ubereats" "Uber Eats logo" "${PARTNERS_DIR}/uber-eats.png"
download_logo "indrive" "inDrive logo" "${PARTNERS_DIR}/indrive.png"
download_logo "mrd" "Mr D Food logo" "${PARTNERS_DIR}/mrd.png"
download_logo "takealot" "Takealot logo" "${PARTNERS_DIR}/takealot.png"
download_logo "sixty60" "Sixty60" "${PARTNERS_DIR}/sixty60.png"

echo "done"
