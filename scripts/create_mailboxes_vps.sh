#!/usr/bin/env bash
set -euo pipefail

# Create multiple system mailbox users on the VPS (for Roundcube/Dovecot).
# Default: generates strong random passwords and prints them once.
#
# Usage:
#   ./scripts/create_mailboxes_vps.sh --host 102.219.85.83 --user root --mailboxes "support info noreply"
#
# Notes:
# - This is the "system users" mailbox model: each mailbox is a Linux user.
# - Roundcube should log in with username "support" (or the mailbox user), not the VPS root password.

VPS_HOST="${VPS_HOST:-}"
VPS_USER="${VPS_USER:-root}"
MAILBOXES="${MAILBOXES:-support info noreply}"
DEPLOY_KEY_PATH="${DEPLOY_KEY_PATH:-$HOME/.ssh/bwiser_deploy_ed25519}"
SSH_OPTS="${SSH_OPTS:- -o StrictHostKeyChecking=accept-new -o ConnectTimeout=15 }"

die() { echo "ERROR: $*" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --host) VPS_HOST="${2:-}"; shift 2;;
    --user) VPS_USER="${2:-}"; shift 2;;
    --mailboxes) MAILBOXES="${2:-}"; shift 2;;
    --key) DEPLOY_KEY_PATH="${2:-}"; shift 2;;
    --help|-h)
      sed -n '1,160p' "$0"
      exit 0
      ;;
    *)
      die "Unknown arg: $1"
      ;;
  esac
done

[[ -n "${VPS_HOST}" ]] || die "Missing --host (or VPS_HOST)."
[[ -n "${VPS_USER}" ]] || die "Missing --user (or VPS_USER)."
[[ -n "${MAILBOXES}" ]] || die "Missing --mailboxes (or MAILBOXES)."

TARGET="${VPS_USER}@${VPS_HOST}"
SSH_OPTS_KEYED="${SSH_OPTS}"
if [[ -f "${DEPLOY_KEY_PATH}" ]]; then
  SSH_OPTS_KEYED="${SSH_OPTS_KEYED} -i ${DEPLOY_KEY_PATH}"
fi

declare -a USERS=()
for u in ${MAILBOXES}; do
  if [[ ! "${u}" =~ ^[a-z_][a-z0-9_-]{0,31}$ ]]; then
    die "Invalid mailbox username '${u}'. Use Linux usernames like: support, info, noreply"
  fi
  USERS+=("${u}")
done

# Create users if missing.
for u in "${USERS[@]}"; do
  echo "Remote: ensure mailbox user '${u}' exists..."
  ssh ${SSH_OPTS_KEYED} "${TARGET}" "bash -lc $(printf '%q' "
set -euo pipefail

# XAMPP can ship a non-standard `head` earlier in PATH on macOS.
export PATH="/usr/bin:/bin:/usr/sbin:/sbin:${PATH}"
if [[ \$(id -u) != 0 ]]; then
  echo 'Must run as root on VPS.' >&2
  exit 40
fi
if id '${u}' >/dev/null 2>&1; then
  exit 0
fi
adduser --disabled-password --gecos '' '${u}'
")"
done

# Generate random passwords locally and set them via chpasswd.
tmp_pwfile="$(mktemp)"
chmod 600 "${tmp_pwfile}"
for u in "${USERS[@]}"; do
  # URL-safe, 24 chars.
  pw="$(LC_ALL=C tr -dc 'A-Za-z0-9_-' </dev/urandom | /usr/bin/head -c 24)"
  printf '%s:%s\n' "${u}" "${pw}" >>"${tmp_pwfile}"
done

echo "Remote: setting mailbox passwords..."
cat "${tmp_pwfile}" | ssh ${SSH_OPTS_KEYED} "${TARGET}" "bash -lc $(printf '%q' "
set -euo pipefail
if [[ \$(id -u) != 0 ]]; then
  echo 'Must run as root on VPS.' >&2
  exit 40
fi
chpasswd
if command -v systemctl >/dev/null 2>&1; then
  systemctl restart dovecot 2>/dev/null || true
  systemctl restart postfix 2>/dev/null || true
fi
")"

echo
echo "Mailbox credentials (save these now):"
cat "${tmp_pwfile}"

rm -f "${tmp_pwfile}"
