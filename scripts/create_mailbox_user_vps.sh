#!/usr/bin/env bash
set -euo pipefail

# Create (or reset) a system mailbox user on the VPS for Roundcube/Dovecot.
#
# For the simple "system users" setup we installed, the Roundcube login username is the Linux username
# (e.g. "support"), and the password is the Linux user password you set here.
#
# Usage:
#   ./scripts/create_mailbox_user_vps.sh --host 102.219.85.83 --user root --mailbox support
#
# Optional:
#   --key ~/.ssh/bwiser_deploy_ed25519
#   --password-env MAILBOX_PASSWORD   (use env var instead of interactive prompt; avoid if possible)

VPS_HOST="${VPS_HOST:-}"
VPS_USER="${VPS_USER:-root}"
MAILBOX_USER="${MAILBOX_USER:-support}"
DEPLOY_KEY_PATH="${DEPLOY_KEY_PATH:-$HOME/.ssh/bwiser_deploy_ed25519}"
SSH_OPTS="${SSH_OPTS:- -o StrictHostKeyChecking=accept-new -o ConnectTimeout=15 }"
PASSWORD_ENV_VAR=""

die() { echo "ERROR: $*" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --host) VPS_HOST="${2:-}"; shift 2;;
    --user) VPS_USER="${2:-}"; shift 2;;
    --mailbox|--mailbox-user) MAILBOX_USER="${2:-}"; shift 2;;
    --key) DEPLOY_KEY_PATH="${2:-}"; shift 2;;
    --password-env) PASSWORD_ENV_VAR="${2:-}"; shift 2;;
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
[[ -n "${MAILBOX_USER}" ]] || die "Missing --mailbox (or MAILBOX_USER)."

TARGET="${VPS_USER}@${VPS_HOST}"

SSH_OPTS_KEYED="${SSH_OPTS}"
if [[ -f "${DEPLOY_KEY_PATH}" ]]; then
  SSH_OPTS_KEYED="${SSH_OPTS_KEYED} -i ${DEPLOY_KEY_PATH}"
fi

MAILBOX_PASSWORD=""
if [[ -n "${PASSWORD_ENV_VAR}" ]]; then
  MAILBOX_PASSWORD="${!PASSWORD_ENV_VAR:-}"
  [[ -n "${MAILBOX_PASSWORD}" ]] || die "Env var ${PASSWORD_ENV_VAR} is empty."
else
  read -r -s -p "Enter new password for '${MAILBOX_USER}' on ${TARGET}: " MAILBOX_PASSWORD
  echo
  read -r -s -p "Confirm password: " MAILBOX_PASSWORD_CONFIRM
  echo
  [[ "${MAILBOX_PASSWORD}" == "${MAILBOX_PASSWORD_CONFIRM}" ]] || die "Passwords do not match."
  [[ -n "${MAILBOX_PASSWORD}" ]] || die "Password cannot be empty."
fi

if [[ ! "${MAILBOX_USER}" =~ ^[a-z_][a-z0-9_-]{0,31}$ ]]; then
  die "Invalid mailbox username '${MAILBOX_USER}'. Use a Linux username like: support"
fi

echo "Remote: ensuring mailbox user '${MAILBOX_USER}' exists on ${TARGET}..."
ssh ${SSH_OPTS_KEYED} "${TARGET}" "bash -lc $(printf '%q' "
set -euo pipefail
if [[ \$(id -u) != 0 ]]; then
  echo 'Must run as root on VPS.' >&2
  exit 40
fi
if id '${MAILBOX_USER}' >/dev/null 2>&1; then
  echo 'User exists.'
else
  adduser --disabled-password --gecos '' '${MAILBOX_USER}'
  echo 'User created.'
fi
")"

echo "Remote: setting password for '${MAILBOX_USER}'..."
printf '%s:%s\n' "${MAILBOX_USER}" "${MAILBOX_PASSWORD}" | ssh ${SSH_OPTS_KEYED} "${TARGET}" "bash -lc $(printf '%q' "
set -euo pipefail
if [[ \$(id -u) != 0 ]]; then
  echo 'Must run as root on VPS.' >&2
  exit 40
fi
chpasswd
")"

ssh ${SSH_OPTS_KEYED} "${TARGET}" "bash -lc $(printf '%q' "
set -euo pipefail
if command -v systemctl >/dev/null 2>&1; then
  systemctl restart dovecot 2>/dev/null || true
  systemctl restart postfix 2>/dev/null || true
fi
echo 'Done. Roundcube login:'
echo '  Username: ${MAILBOX_USER}'
")"
