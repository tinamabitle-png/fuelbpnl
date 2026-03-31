#!/usr/bin/env bash
set -euo pipefail

OUT_DIR="${1:-storage/ssl/localhost}"

mkdir -p "$OUT_DIR"

CNF="$OUT_DIR/openssl-localhost.cnf"
KEY="$OUT_DIR/localhost.key"
CRT="$OUT_DIR/localhost.crt"
PEM="$OUT_DIR/localhost.pem"

cat > "$CNF" <<'EOF'
[req]
default_bits = 2048
prompt = no
default_md = sha256
distinguished_name = dn
x509_extensions = v3_req

[dn]
C = ZA
ST = Gauteng
L = Johannesburg
O = Bwiser
OU = Local Dev
CN = localhost

[v3_req]
subjectAltName = @alt_names
keyUsage = keyEncipherment, dataEncipherment, digitalSignature
extendedKeyUsage = serverAuth

[alt_names]
DNS.1 = localhost
IP.1 = 127.0.0.1
IP.2 = ::1
EOF

openssl req -x509 -nodes -days 825 -newkey rsa:2048 \
  -keyout "$KEY" \
  -out "$CRT" \
  -config "$CNF"

cat "$CRT" "$KEY" > "$PEM"

chmod 600 "$KEY"

echo "Wrote:"
echo "  $KEY"
echo "  $CRT"
echo "  $PEM"
echo "  $CNF"

