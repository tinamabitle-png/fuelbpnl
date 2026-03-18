#!/usr/bin/env bash
set -euo pipefail

# Provision a basic mail stack on a VPS:
# - Postfix (SMTP)
# - OpenDKIM (DKIM signing)
# - OpenDMARC (DMARC reporting/policy, optional but recommended)
#
# This script is intended for Ubuntu/Debian (apt). It prints the DNS records
# you must add in Cloudflare and reminders about PTR/rDNS.
#
# Usage:
#   ./scripts/provision_mail_vps.sh --host 102.219.85.83 --user root --domain bwiser.co.za
#
# Optional:
#   --mail-host mail.bwiser.co.za
#   --selector default
#   --dmarc-rua dmarc@bwiser.co.za
#
# Notes:
# - Requires root on the VPS.
# - Does NOT open firewall ports automatically.
# - Does NOT configure IMAP (Dovecot) mailboxes. This is "send mail reliably"
#   plus the DNS/auth pieces required for deliverability.

VPS_HOST="${VPS_HOST:-}"
VPS_USER="${VPS_USER:-root}"
MAIL_DOMAIN="${MAIL_DOMAIN:-bwiser.co.za}"
MAIL_HOSTNAME="${MAIL_HOSTNAME:-mail.bwiser.co.za}"
DKIM_SELECTOR="${DKIM_SELECTOR:-default}"
DMARC_RUA="${DMARC_RUA:-dmarc@bwiser.co.za}"

SSH_OPTS="${SSH_OPTS:- -o StrictHostKeyChecking=accept-new -o ConnectTimeout=15 }"
DEPLOY_KEY_PATH="${DEPLOY_KEY_PATH:-$HOME/.ssh/bwiser_deploy_ed25519}"

die() { echo "ERROR: $*" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --host) VPS_HOST="${2:-}"; shift 2;;
    --user) VPS_USER="${2:-}"; shift 2;;
    --domain) MAIL_DOMAIN="${2:-}"; shift 2;;
    --mail-host) MAIL_HOSTNAME="${2:-}"; shift 2;;
    --selector) DKIM_SELECTOR="${2:-}"; shift 2;;
    --dmarc-rua) DMARC_RUA="${2:-}"; shift 2;;
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
[[ -n "${MAIL_HOSTNAME}" ]] || die "Missing --mail-host (or MAIL_HOSTNAME)."
[[ -n "${DKIM_SELECTOR}" ]] || die "Missing --selector (or DKIM_SELECTOR)."

TARGET="${VPS_USER}@${VPS_HOST}"
SSH_OPTS_KEYED="${SSH_OPTS}"
if [[ -f "${DEPLOY_KEY_PATH}" ]]; then
  SSH_OPTS_KEYED="${SSH_OPTS_KEYED} -i ${DEPLOY_KEY_PATH}"
fi

remote_script='
set -euo pipefail

MAIL_DOMAIN="'"${MAIL_DOMAIN}"'"
MAIL_HOSTNAME="'"${MAIL_HOSTNAME}"'"
DKIM_SELECTOR="'"${DKIM_SELECTOR}"'"
DMARC_RUA="'"${DMARC_RUA}"'"

if [[ "$(id -u)" != "0" ]]; then
  echo "Must run as root on VPS."; exit 40
fi

if ! command -v apt-get >/dev/null 2>&1; then
  echo "This provisioning script currently supports Debian/Ubuntu (apt-get)."; exit 41
fi

export DEBIAN_FRONTEND=noninteractive

echo "Installing packages..."
apt-get update -y

# Preseed postfix prompts.
echo "postfix postfix/mailname string ${MAIL_HOSTNAME}" | debconf-set-selections
echo "postfix postfix/main_mailer_type select Internet Site" | debconf-set-selections

apt-get install -y \
  postfix \
  opendkim opendkim-tools \
  opendmarc \
  ca-certificates

echo "Configuring Postfix hostname/domain..."
postconf -e "myhostname = ${MAIL_HOSTNAME}"
postconf -e "mydomain = ${MAIL_DOMAIN}"
postconf -e "myorigin = \$mydomain"
postconf -e "mydestination = \$myhostname, localhost.\$mydomain, localhost"
postconf -e "inet_interfaces = all"
postconf -e "inet_protocols = all"

# Basic hardening defaults (safe baseline).
postconf -e "smtpd_helo_required = yes"
postconf -e "smtpd_tls_security_level = may"
postconf -e "smtp_tls_security_level = may"
postconf -e "smtpd_tls_loglevel = 1"
postconf -e "smtp_tls_loglevel = 1"

echo "Configuring OpenDKIM..."
mkdir -p /etc/opendkim/keys/"${MAIL_DOMAIN}"
chmod 750 /etc/opendkim/keys
chmod 750 /etc/opendkim/keys/"${MAIL_DOMAIN}"

cat >/etc/opendkim.conf <<EOF
Syslog                  yes
SyslogSuccess           yes
LogWhy                  no

UMask                   002

Mode                    sv
Canonicalization        relaxed/simple
SubDomains              no

AutoRestart             yes
AutoRestartRate         10/1h

Socket                  local:/var/spool/postfix/opendkim/opendkim.sock
PidFile                 /run/opendkim/opendkim.pid

UserID                  opendkim

KeyTable                /etc/opendkim/key.table
SigningTable            /etc/opendkim/signing.table
ExternalIgnoreList      /etc/opendkim/trusted.hosts
InternalHosts           /etc/opendkim/trusted.hosts
EOF

cat >/etc/opendkim/trusted.hosts <<EOF
127.0.0.1
localhost
${MAIL_HOSTNAME}
EOF

cat >/etc/opendkim/key.table <<EOF
${DKIM_SELECTOR}._domainkey.${MAIL_DOMAIN} ${MAIL_DOMAIN}:${DKIM_SELECTOR}:/etc/opendkim/keys/${MAIL_DOMAIN}/${DKIM_SELECTOR}.private
EOF

cat >/etc/opendkim/signing.table <<EOF
*@${MAIL_DOMAIN} ${DKIM_SELECTOR}._domainkey.${MAIL_DOMAIN}
EOF

KEY_PRIV="/etc/opendkim/keys/${MAIL_DOMAIN}/${DKIM_SELECTOR}.private"
KEY_TXT="/etc/opendkim/keys/${MAIL_DOMAIN}/${DKIM_SELECTOR}.txt"

if [[ ! -f "${KEY_PRIV}" ]]; then
  echo "Generating DKIM key (${DKIM_SELECTOR})..."
  opendkim-genkey -b 2048 -s "${DKIM_SELECTOR}" -d "${MAIL_DOMAIN}" -D "/etc/opendkim/keys/${MAIL_DOMAIN}"
  mv -f "/etc/opendkim/keys/${MAIL_DOMAIN}/${DKIM_SELECTOR}.private" "${KEY_PRIV}" || true
fi

chown -R opendkim:opendkim /etc/opendkim
chmod 640 "${KEY_PRIV}"
chmod 644 "${KEY_TXT}" || true

mkdir -p /var/spool/postfix/opendkim
chown opendkim:postfix /var/spool/postfix/opendkim
chmod 750 /var/spool/postfix/opendkim

echo "Hooking OpenDKIM into Postfix (milter)..."
postconf -e "milter_default_action = accept"
postconf -e "milter_protocol = 6"
postconf -e "smtpd_milters = unix:/opendkim/opendkim.sock"
postconf -e "non_smtpd_milters = unix:/opendkim/opendkim.sock"

echo "Configuring OpenDMARC (milter)..."
cat >/etc/opendmarc.conf <<EOF
AuthservID              ${MAIL_HOSTNAME}
PidFile                 /run/opendmarc/opendmarc.pid
RejectFailures          false
RequiredHeaders         true
SPFIgnoreResults        false

Socket                  local:/var/spool/postfix/opendmarc/opendmarc.sock
Syslog                  true

UMask                   002
UserID                  opendmarc
EOF

mkdir -p /var/spool/postfix/opendmarc
chown opendmarc:postfix /var/spool/postfix/opendmarc
chmod 750 /var/spool/postfix/opendmarc

postconf -e "smtpd_milters = unix:/opendkim/opendkim.sock, unix:/opendmarc/opendmarc.sock"
postconf -e "non_smtpd_milters = unix:/opendkim/opendkim.sock, unix:/opendmarc/opendmarc.sock"

echo "Restarting services..."
systemctl enable --now opendkim
systemctl enable --now opendmarc
systemctl restart postfix

echo
echo "=== DNS records to add in Cloudflare (manual) ==="
echo
echo "A record:"
echo "  Name: mail"
echo "  Type: A"
echo "  Content: (your VPS IP)"
echo "  Proxy: DNS only (NOT proxied)"
echo
echo "MX record:"
echo "  Name: @"
echo "  Type: MX"
echo "  Mail server: ${MAIL_HOSTNAME}"
echo "  Priority: 10"
echo
echo "SPF TXT (example):"
echo "  Name: @"
echo "  Type: TXT"
echo "  Content: v=spf1 mx ip4:YOUR_VPS_IP -all"
echo
echo "DMARC TXT (starter policy):"
echo "  Name: _dmarc"
echo "  Type: TXT"
echo "  Content: v=DMARC1; p=quarantine; rua=mailto:${DMARC_RUA}; adkim=s; aspf=s"
echo
echo "DKIM TXT:"
echo "  Name: ${DKIM_SELECTOR}._domainkey"
echo "  Type: TXT"
echo "  Content:"
if [[ -f "${KEY_TXT}" ]]; then
  # Concatenate quoted strings from the generated .txt into one TXT value.
  awk -F"\"" "NF>1 { for (i=2; i<=NF; i+=2) printf \"%s\", \$i } END { print \"\" }" "${KEY_TXT}"
else
  echo "  (DKIM key txt file not found: ${KEY_TXT})"
fi
echo
echo "IMPORTANT: Ask your VPS provider to set PTR/rDNS:"
echo "  YOUR_VPS_IP -> ${MAIL_HOSTNAME}"
echo
echo "Service status:"
systemctl --no-pager --full status postfix | sed -n "1,8p" || true
systemctl --no-pager --full status opendkim | sed -n "1,8p" || true
systemctl --no-pager --full status opendmarc | sed -n "1,8p" || true
echo
echo "Done."
'

echo "Remote: provisioning mail on ${TARGET} for domain ${MAIL_DOMAIN} (${MAIL_HOSTNAME})..."
ssh ${SSH_OPTS_KEYED} "${TARGET}" "bash -lc $(printf '%q' "${remote_script}")"
