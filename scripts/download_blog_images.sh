#!/usr/bin/env bash
set -euo pipefail

# Downloads context-appropriate, royalty-free images (via Unsplash Source) for the SEO blog posts
# and stores them locally so they don't randomly change or fail to load in production.
#
# Usage:
#   bash scripts/download_blog_images.sh

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

OUT_DIR="public/images/blog"
mkdir -p "$OUT_DIR"

# Wikimedia (and many CDNs) expect a descriptive UA with contact info for automated fetching.
UA="BwiserImageFetcher/1.0 (+https://bwiser.co.za; support@bwiser.co.za)"
SLEEP_BETWEEN_DOWNLOADS_SEC=2

php -r '
$posts = (array) require "config/blog_posts.php";
foreach ($posts as $p) {
    if (!is_array($p)) continue;
    $slug = trim((string)($p["slug"] ?? ""));
    if ($slug === "") continue;
    $src = (string)($p["image_source_url"] ?? "");
    if ($src === "") $src = (string)($p["image_url"] ?? "");
    if ($src === "") continue;
    echo $slug, "\t", $src, "\n";
}
' | while IFS=$'\t' read -r slug src; do
  dest="${OUT_DIR}/${slug}.jpg"
  tmp="${dest}.tmp"

  if [[ -s "$dest" ]]; then
    echo "ok: $dest"
    continue
  fi

  echo "downloading: $slug"

  # Resolve to a royalty-free image via Wikimedia Commons (open API).
  # Unsplash endpoints are currently unreliable in this environment (source.unsplash.com 503, unsplash.com napi blocked).
  query=""
  if [[ "$src" == *"source.unsplash.com"* && "$src" == *\?* ]]; then
    query="${src#*\?}"
    query="${query//,/ }"
  fi

  candidates=""
  if [[ -n "$query" ]]; then
    base="${query//-/ }"
    queries=(
      "$base"
      "$base petrol station"
      "$base fuel"
      "petrol station fuel south africa"
      "fuel tanker petrol station"
    )

    for q in "${queries[@]}"; do
      q_enc="$(php -r 'echo rawurlencode($argv[1]);' "$q")"
      api="https://commons.wikimedia.org/w/api.php?action=query&format=json&generator=search&gsrnamespace=6&gsrlimit=5&gsrsearch=${q_enc}&prop=imageinfo&iiprop=url&iiurlwidth=1600"
      json="$(curl -s --fail --connect-timeout 10 --max-time 30 \
        -H "User-Agent: ${UA}" \
        "$api" || true)"

      urls=""
      if [[ -n "${json:-}" ]]; then
        urls="$(php -r '
          $j = json_decode(stream_get_contents(STDIN), true);
          $pages = (array)($j["query"]["pages"] ?? []);
          $out = [];
          foreach ($pages as $p) {
            $idx = (int)($p["index"] ?? 999999);
            $ii = (array)($p["imageinfo"][0] ?? []);
            $u = (string)($ii["thumburl"] ?? $ii["url"] ?? "");
            if ($u !== "") $out[] = [$idx, $u];
          }
          usort($out, fn($a,$b) => $a[0] <=> $b[0]);
          foreach ($out as $row) echo $row[1], "\n";
        ' <<<"$json")"
      fi

      if [[ "${BWISER_DEBUG:-}" == "1" ]]; then
        echo "query_try: $q" >&2
        echo "commons_api: $api" >&2
        echo "urls_head: ${urls:0:160}" >&2
      fi

      if [[ -n "${urls:-}" ]]; then
        candidates="$urls"
        break
      fi
    done
  fi

  if [[ -z "${candidates:-}" ]]; then
    candidates="$src"
  fi

  if [[ "${BWISER_DEBUG:-}" == "1" ]]; then
    echo "src_url: $src" >&2
    echo "candidates_head: ${candidates:0:200}" >&2
  fi

  downloaded=0
  while IFS= read -r resolved; do
    resolved="$(echo "$resolved" | tr -d '\r')"
    [[ -z "$resolved" ]] && continue

    rm -f "$tmp"
    attempt=1
    while :; do
      if curl -L --fail --silent --show-error \
        --connect-timeout 10 --max-time 45 \
        --retry 6 --retry-delay 2 --retry-connrefused \
        -H "User-Agent: ${UA}" \
        "$resolved" -o "$tmp"; then
        downloaded=1
        break
      fi

      if [[ $attempt -ge 6 ]]; then
        break
      fi

      sleep_for=$((attempt * 5))
      echo "retrying ($attempt/6) in ${sleep_for}s: $slug" >&2
      sleep "$sleep_for"
      attempt=$((attempt + 1))
    done

    if [[ $downloaded -eq 1 ]]; then
      break
    fi
  done <<< "$candidates"

  if [[ $downloaded -ne 1 ]]; then
    echo "error: failed downloading $slug (all candidates failed)" >&2
    rm -f "$tmp"
    exit 1
  fi

  # Basic sanity: ensure non-empty file.
  if [[ ! -s "$tmp" ]]; then
    echo "error: empty download for $slug" >&2
    rm -f "$tmp"
    exit 1
  fi

  mv "$tmp" "$dest"
  sleep "$SLEEP_BETWEEN_DOWNLOADS_SEC"
done

echo "done: $OUT_DIR"
