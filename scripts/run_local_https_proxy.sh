#!/usr/bin/env bash
set -euo pipefail

# Assumes your app is already running on http://127.0.0.1:8000 (e.g. php artisan serve).
# This starts an HTTPS reverse proxy on https://localhost:8443 using the self-signed cert.

if [[ ! -f "storage/ssl/localhost/localhost.key" || ! -f "storage/ssl/localhost/localhost.crt" ]]; then
  bash scripts/create_localhost_cert.sh
fi

node tools/dev/https_reverse_proxy.mjs --listen 8443 --target http://127.0.0.1:8000

