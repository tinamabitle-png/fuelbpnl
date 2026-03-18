#!/usr/bin/env bash
set -euo pipefail

# Provision Roundcube webmail on a VPS (Ubuntu/Debian via apt).
#
# Result:
# - Webmail URL: https://webmail.<domain>
#
# What it does:
# - Installs nginx + php-fpm + roundcube
# - Optionally installs dovecot-imapd (needed if you want mailboxes on the VPS)
# - Configures nginx vhost for Roundcube
# - Runs certbot to issue TLS cert for the webmail subdomain
# - Configures Roundcube to use your IMAP/SMTP host (usually mail.<domain>)
#
# Usage:
#   ./scripts/provision_webmail_roundcube_vps.sh --host 102.219.85.83 --user root --domain bwiser.co.za
#
# Options:
#   --webmail-host webmail.bwiser.co.za
#   --mail-host mail.bwiser.co.za
#   --with-dovecot true|false   (default: true)
#   --php-version 8.2           (optional; if omitted, uses distro default php-fpm)
#
# Notes:
# - Ensure Cloudflare DNS A record exists for webmail.<domain> -> VPS IP and is DNS-only.
# - certbot requires the domain to already resolve to this VPS.

VPS_HOST="${VPS_HOST:-}"
VPS_USER="${VPS_USER:-root}"
MAIL_DOMAIN="${MAIL_DOMAIN:-bwiser.co.za}"
WEBMAIL_HOSTNAME="${WEBMAIL_HOSTNAME:-webmail.bwiser.co.za}"
MAIL_HOSTNAME="${MAIL_HOSTNAME:-mail.bwiser.co.za}"
WITH_DOVECOT="${WITH_DOVECOT:-true}"
PHP_VERSION="${PHP_VERSION:-}"

SSH_OPTS="${SSH_OPTS:- -o StrictHostKeyChecking=accept-new -o ConnectTimeout=15 }"
DEPLOY_KEY_PATH="${DEPLOY_KEY_PATH:-$HOME/.ssh/bwiser_deploy_ed25519}"

die() { echo "ERROR: $*" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --host) VPS_HOST="${2:-}"; shift 2;;
    --user) VPS_USER="${2:-}"; shift 2;;
    --domain) MAIL_DOMAIN="${2:-}"; shift 2;;
    --webmail-host) WEBMAIL_HOSTNAME="${2:-}"; shift 2;;
    --mail-host) MAIL_HOSTNAME="${2:-}"; shift 2;;
    --with-dovecot) WITH_DOVECOT="${2:-}"; shift 2;;
    --php-version) PHP_VERSION="${2:-}"; shift 2;;
    --key) DEPLOY_KEY_PATH="${2:-}"; shift 2;;
    --help|-h)
      sed -n '1,200p' "$0"
      exit 0
      ;;
    *)
      die "Unknown arg: $1"
      ;;
  esac
done

[[ -n "${VPS_HOST}" ]] || die "Missing --host (or VPS_HOST)."
[[ -n "${VPS_USER}" ]] || die "Missing --user (or VPS_USER)."
[[ -n "${MAIL_DOMAIN}" ]] || die "Missing --domain (or MAIL_DOMAIN)."
[[ -n "${WEBMAIL_HOSTNAME}" ]] || die "Missing --webmail-host (or WEBMAIL_HOSTNAME)."
[[ -n "${MAIL_HOSTNAME}" ]] || die "Missing --mail-host (or MAIL_HOSTNAME)."

TARGET="${VPS_USER}@${VPS_HOST}"
SSH_OPTS_KEYED="${SSH_OPTS}"
if [[ -f "${DEPLOY_KEY_PATH}" ]]; then
  SSH_OPTS_KEYED="${SSH_OPTS_KEYED} -i ${DEPLOY_KEY_PATH}"
fi

remote_script='
set -euo pipefail

MAIL_DOMAIN="'"${MAIL_DOMAIN}"'"
WEBMAIL_HOSTNAME="'"${WEBMAIL_HOSTNAME}"'"
MAIL_HOSTNAME="'"${MAIL_HOSTNAME}"'"
WITH_DOVECOT="'"${WITH_DOVECOT}"'"
PHP_VERSION="'"${PHP_VERSION}"'"

if [[ "$(id -u)" != "0" ]]; then
  echo "Must run as root on VPS."; exit 40
fi

if ! command -v apt-get >/dev/null 2>&1; then
  echo "This provisioning script currently supports Debian/Ubuntu (apt-get)."; exit 41
fi

export DEBIAN_FRONTEND=noninteractive

echo "Installing nginx + PHP-FPM + Roundcube..."
apt-get update -y

PHP_FPM_PKG="php-fpm"
PHP_FPM_SOCK=""
if [[ -n "${PHP_VERSION}" ]]; then
  PHP_FPM_PKG="php${PHP_VERSION}-fpm"
  PHP_FPM_SOCK="/run/php/php${PHP_VERSION}-fpm.sock"
fi

apt-get install -y \
  nginx \
  certbot python3-certbot-nginx \
  "${PHP_FPM_PKG}" \
  php-cli php-common php-mbstring php-xml php-curl php-zip php-gd php-intl php-mysql \
  roundcube roundcube-core roundcube-mysql

if [[ "${WITH_DOVECOT}" == "true" ]]; then
  echo "Installing Dovecot (IMAP)..."
  apt-get install -y dovecot-imapd
  systemctl enable --now dovecot || true
fi

echo "Detecting PHP-FPM socket..."
if [[ -z "${PHP_FPM_SOCK}" ]]; then
  # Try common sockets (Ubuntu/Debian).
  for sock in /run/php/php*-fpm.sock; do
    if [[ -S "${sock}" ]]; then
      PHP_FPM_SOCK="${sock}"
      break
    fi
  done
fi
if [[ -z "${PHP_FPM_SOCK}" || ! -S "${PHP_FPM_SOCK}" ]]; then
  echo "Could not detect php-fpm socket. Found:"; ls -lah /run/php || true
  exit 42
fi
echo "Using PHP-FPM socket: ${PHP_FPM_SOCK}"

echo "Configuring Roundcube..."
RC_CFG="/etc/roundcube/config.inc.php"
if [[ ! -f "${RC_CFG}" ]]; then
  echo "Roundcube config not found at ${RC_CFG}"; exit 43
fi

cp -n "${RC_CFG}" "${RC_CFG}.bak.$(date +%Y%m%d%H%M%S)" || true

# Ensure PHP files have a writable temp dir.
mkdir -p /var/lib/roundcube/temp /var/lib/roundcube/logs
chown -R www-data:www-data /var/lib/roundcube/temp /var/lib/roundcube/logs

# Configure IMAP/SMTP hosts and set smtp auth to use the same creds as login.
perl -0777 -i -pe "s#\\$config\\['default_host'\\]\\s*=\\s*.*?;#\\$config['default_host'] = 'ssl://${MAIL_HOSTNAME}';#s" "${RC_CFG}" || true
perl -0777 -i -pe "s#\\$config\\['smtp_server'\\]\\s*=\\s*.*?;#\\$config['smtp_server'] = 'tls://${MAIL_HOSTNAME}';#s" "${RC_CFG}" || true
perl -0777 -i -pe "s#\\$config\\['smtp_port'\\]\\s*=\\s*.*?;#\\$config['smtp_port'] = 587;#s" "${RC_CFG}" || true

if ! grep -q \"smtp_user\" \"${RC_CFG}\"; then
  cat >>\"${RC_CFG}\" <<EOF

// Use the same credentials the user logs in with.
\$config['smtp_user'] = '%u';
\$config['smtp_pass'] = '%p';
EOF
fi

if ! grep -q \"des_key\" \"${RC_CFG}\"; then
  # Newer configs may not contain it; keep Roundcube happy.
  DES_KEY="$(openssl rand -hex 24)"
  echo \"\\$config['des_key'] = '${DES_KEY}';\" >>\"${RC_CFG}\"
fi

echo "Configuring nginx vhost for ${WEBMAIL_HOSTNAME}..."
NGINX_SITE="/etc/nginx/sites-available/webmail"
cat >"${NGINX_SITE}" <<EOF
server {
  listen 80;
  listen [::]:80;
  server_name ${WEBMAIL_HOSTNAME};

  root /usr/share/roundcube;
  index index.php index.html;

  access_log /var/log/nginx/webmail.access.log;
  error_log  /var/log/nginx/webmail.error.log;

  location / {
    try_files \$uri \$uri/ /index.php?\$args;
  }

  location ~ \\.php\$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:${PHP_FPM_SOCK};
  }

  location ~ /\\. {
    deny all;
  }
}
EOF

ln -sf "${NGINX_SITE}" /etc/nginx/sites-enabled/webmail
rm -f /etc/nginx/sites-enabled/default || true

nginx -t
systemctl reload nginx

echo "Requesting TLS cert via certbot for ${WEBMAIL_HOSTNAME}..."
certbot --nginx -d "${WEBMAIL_HOSTNAME}" --non-interactive --agree-tos -m "support@${MAIL_DOMAIN}" --redirect

echo
echo "Webmail is ready:"
echo "  https://${WEBMAIL_HOSTNAME}"
echo
echo "If you installed Dovecot and want a mailbox like support@${MAIL_DOMAIN}:"
echo "  adduser support"
echo "Then login to Roundcube with:"
echo "  Username: support@${MAIL_DOMAIN}"
echo "  Password: (the Linux user password you set)"
echo
echo "Done."
'

echo "Remote: provisioning Roundcube webmail on ${TARGET} (${WEBMAIL_HOSTNAME})..."
ssh ${SSH_OPTS_KEYED} "${TARGET}" "bash -lc $(printf '%q' "${remote_script}")"

