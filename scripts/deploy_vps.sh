#!/usr/bin/env bash
set -euo pipefail

# Push local commits and deploy to a VPS over SSH (Laravel).
#
# Usage:
#   ./scripts/deploy_vps.sh --host 102.219.85.83 --user root --dir /var/www/html/fuellevy
#
# Or with env vars:
#   VPS_HOST=102.219.85.83 VPS_USER=root VPS_DIR=/var/www/html/fuellevy ./scripts/deploy_vps.sh
#
# Notes:
# - Requires SSH access (keys recommended).
# - Runs "php artisan migrate --force" on the VPS to apply latest migrations.

BRANCH="main"
REMOTE="origin"
VPS_HOST="${VPS_HOST:-102.219.85.83}"
VPS_USER="${VPS_USER:-root}"
VPS_DIR="${VPS_DIR:-}"
REMOTE_PHP="${REMOTE_PHP:-php}"
REMOTE_COMPOSER="${REMOTE_COMPOSER:-composer}"
SSH_OPTS="${SSH_OPTS:- -o StrictHostKeyChecking=accept-new -o ConnectTimeout=15 }"
DEPLOY_KEY_PATH="${DEPLOY_KEY_PATH:-$HOME/.ssh/bwiser_deploy_ed25519}"
INSTALL_KEY="false"

die() { echo "ERROR: $*" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --branch) BRANCH="${2:-}"; shift 2;;
    --remote) REMOTE="${2:-}"; shift 2;;
    --host) VPS_HOST="${2:-}"; shift 2;;
    --user) VPS_USER="${2:-}"; shift 2;;
    --dir) VPS_DIR="${2:-}"; shift 2;;
    --php) REMOTE_PHP="${2:-}"; shift 2;;
    --composer) REMOTE_COMPOSER="${2:-}"; shift 2;;
    --key) DEPLOY_KEY_PATH="${2:-}"; shift 2;;
    --install-key) INSTALL_KEY="true"; shift 1;;
    --help|-h)
      sed -n '1,140p' "$0"
      exit 0
      ;;
    *)
      die "Unknown arg: $1"
      ;;
  esac
done

[[ -n "${VPS_HOST}" ]] || die "Missing --host (or VPS_HOST)."
[[ -n "${VPS_USER}" ]] || die "Missing --user (or VPS_USER)."
[[ -n "${BRANCH}" ]] || die "Missing --branch."

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  die "Run this from inside the git repo."
fi

echo "Local: pushing ${REMOTE}/${BRANCH}..."
git push "${REMOTE}" "${BRANCH}"

TARGET="${VPS_USER}@${VPS_HOST}"

# Ensure we have a local deploy key to use for SSH. If it's not installed on the VPS yet,
# you can run with --install-key (it will prompt you for the VPS password interactively).
if [[ ! -f "${DEPLOY_KEY_PATH}" ]]; then
  echo "Local: creating SSH deploy key: ${DEPLOY_KEY_PATH}"
  mkdir -p "$(dirname "${DEPLOY_KEY_PATH}")"
  chmod 700 "$(dirname "${DEPLOY_KEY_PATH}")"
  ssh-keygen -t ed25519 -N "" -f "${DEPLOY_KEY_PATH}" -C "bwiser-deploy" >/dev/null
fi

PUBKEY_PATH="${DEPLOY_KEY_PATH}.pub"
[[ -f "${PUBKEY_PATH}" ]] || die "Missing public key: ${PUBKEY_PATH}"

SSH_OPTS_KEYED="${SSH_OPTS} -i ${DEPLOY_KEY_PATH}"

echo "Remote: deploying to ${TARGET} (branch: ${BRANCH})..."

# Fast auth check (won't prompt). If it fails, offer key install guidance.
if ! ssh ${SSH_OPTS_KEYED} -o BatchMode=yes "${TARGET}" "echo ok" >/dev/null 2>&1; then
  if [[ "${INSTALL_KEY}" == "true" ]]; then
    if command -v ssh-copy-id >/dev/null 2>&1; then
      echo "Remote: installing SSH key via ssh-copy-id (you may be prompted for the VPS password)..."
      ssh-copy-id -i "${PUBKEY_PATH}" ${SSH_OPTS} "${TARGET}"
    else
      echo "ssh-copy-id not found. Install the key manually by running this on your machine:"
      echo
      echo "  cat ${PUBKEY_PATH} | ssh ${SSH_OPTS} ${TARGET} 'mkdir -p ~/.ssh && chmod 700 ~/.ssh && cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys'"
      echo
      exit 21
    fi
  else
    echo "Remote: SSH key auth is not set up for ${TARGET}."
    echo "Run this to install the deploy key (recommended):"
    echo
    echo "  ./scripts/deploy_vps.sh --host ${VPS_HOST} --user ${VPS_USER} --install-key"
    echo
    echo "Or add this public key to ${TARGET}:~/.ssh/authorized_keys:"
    echo
    cat "${PUBKEY_PATH}"
    echo
    exit 20
  fi
fi

# At this point key auth should work.

remote_script='
set -euo pipefail

BRANCH="'"${BRANCH}"'"
REMOTE="'"${REMOTE}"'"
REMOTE_PHP="'"${REMOTE_PHP}"'"
REMOTE_COMPOSER="'"${REMOTE_COMPOSER}"'"
APP_DIR="'"${VPS_DIR}"'"

pick_dir() {
  local candidates=()
  if [[ -n "${APP_DIR}" ]]; then
    candidates+=("${APP_DIR}")
  fi
  # Common locations
  candidates+=(
    "/var/www/fuellevy"
    "/var/www/html/fuellevy"
    "/var/www/html"
    "/srv/www/fuellevy"
    "$HOME/fuellevy"
    "$HOME/www/fuellevy"
    "$HOME/public_html/fuellevy"
  )

  for d in "${candidates[@]}"; do
    [[ -d "$d" ]] || continue
    if [[ -f "$d/artisan" ]]; then
      echo "$d"
      return 0
    fi
  done

  # Last resort: find artisan under HOME
  local found
  found="$(find "$HOME" -maxdepth 6 -type f -name artisan 2>/dev/null | head -n 1 || true)"
  if [[ -n "$found" ]]; then
    dirname "$found"
    return 0
  fi

  return 1
}

APP_DIR="$(pick_dir)" || { echo "Could not locate Laravel project directory (artisan not found)."; exit 22; }
echo "Project dir: $APP_DIR"
cd "$APP_DIR"

if [[ ! -d .git ]]; then
  echo "No .git folder in $APP_DIR (not a git checkout)."; exit 23;
fi

echo "Git pull..."
if [[ -n "$(git status --porcelain)" ]]; then
  echo "Stashing local changes on VPS..."
  git stash -u >/dev/null || true
fi
git pull "$REMOTE" "$BRANCH"

echo "Composer install..."
if command -v "$REMOTE_COMPOSER" >/dev/null 2>&1; then
  # Safe path first: avoid script failures when the app cannot boot mid-install.
  "$REMOTE_COMPOSER" install --no-interaction --prefer-dist --no-dev --optimize-autoloader --no-scripts
  "$REMOTE_COMPOSER" install --no-interaction --prefer-dist --no-dev --optimize-autoloader
else
  echo "composer not found on VPS (REMOTE_COMPOSER=$REMOTE_COMPOSER)."; exit 24;
fi

echo "Migrations..."
"$REMOTE_PHP" artisan migrate --force

echo "Caches..."
"$REMOTE_PHP" artisan optimize:clear
"$REMOTE_PHP" artisan queue:restart || true

echo "Deploy complete."
'

ssh ${SSH_OPTS_KEYED} "${TARGET}" "bash -lc $(printf '%q' "${remote_script}")"
