#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# scripts/o2-init-db.sh - Creation/import MySQL Registers sur o2switch
#
# Variables utiles :
#   SSH_USER=zece2169
#   SSH_HOST=dark.o2switch.net
#   SSH_KEY=$HOME/.ssh/mailvio_deploy
#   REMOTE_DIR=registers
#   CPANEL_DB_SHORT=registers
#   CPANEL_DB_USER_SHORT=registers
#   REGISTERS_DB_PASS=...  # optionnel ; genere automatiquement sinon
# =============================================================================

SSH_USER="${SSH_USER:-zece2169}"
SSH_HOST="${SSH_HOST:-dark.o2switch.net}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/mailvio_deploy}"
REMOTE_DIR="${REMOTE_DIR:-registers}"
CPANEL_DB_SHORT="${CPANEL_DB_SHORT:-registers}"
CPANEL_DB_USER_SHORT="${CPANEL_DB_USER_SHORT:-registers}"
LOCAL_DIR="$(cd "$(dirname "$0")/.." && pwd)"
LOCAL_SCHEMA="$LOCAL_DIR/sql/001_create_registers_schema.sql"

DB_NAME="${SSH_USER}_${CPANEL_DB_SHORT}"
UAPI_DB_NAME="$DB_NAME"
UAPI_DB_USER="${SSH_USER}_${CPANEL_DB_USER_SHORT}"
DB_USER="${SSH_USER}_${UAPI_DB_USER}"
DB_PASS="${REGISTERS_DB_PASS:-$(openssl rand -hex 16)}"

TMP_SCHEMA="$(mktemp)"
trap 'rm -f "$TMP_SCHEMA"' EXIT

awk '
  /^CREATE DATABASE / { skip = 1; next }
  skip && /;/ { skip = 0; next }
  skip { next }
  /^USE `Registers`;/ { next }
  { print }
' "$LOCAL_SCHEMA" > "$TMP_SCHEMA"

echo "Creation/verif base MySQL o2switch : $DB_NAME"

scp -i "$SSH_KEY" "$TMP_SCHEMA" "${SSH_USER}@${SSH_HOST}:~/registers_schema.sql" >/dev/null

ssh -i "$SSH_KEY" "${SSH_USER}@${SSH_HOST}" \
  "UAPI_DB_NAME='$UAPI_DB_NAME' UAPI_DB_USER='$UAPI_DB_USER' DB_NAME='$DB_NAME' DB_USER='$DB_USER' DB_PASS='$DB_PASS' REMOTE_DIR='$REMOTE_DIR' bash -s" <<'ENDSSH'
set -euo pipefail

mkdir -p "$HOME/$REMOTE_DIR"

uapi Mysql create_database name="$UAPI_DB_NAME" >/tmp/registers_create_db.log 2>&1 || true
uapi Mysql create_user name="$UAPI_DB_USER" password="$DB_PASS" >/tmp/registers_create_user.log 2>&1 || true
uapi Mysql set_password user="$DB_USER" password="$DB_PASS" >/tmp/registers_set_password.log 2>&1 || true
uapi Mysql set_privileges_on_database user="$DB_USER" database="$DB_NAME" privileges="ALL PRIVILEGES" >/tmp/registers_privileges.log 2>&1 || true

cat > "$HOME/$REMOTE_DIR/.env" <<ENV
REGISTERS_DB_HOST=localhost
REGISTERS_DB_NAME=$DB_NAME
REGISTERS_DB_USER=$DB_USER
REGISTERS_DB_PASS=$DB_PASS
REGISTERS_DB_SOCKET=
PAPPERS_API_KEY=
ENV
chmod 600 "$HOME/$REMOTE_DIR/.env"

mysql --default-character-set=utf8mb4 -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$HOME/registers_schema.sql"
rm -f "$HOME/registers_schema.sql"

echo "OK - Base importee : $DB_NAME"
echo "OK - Fichier env : ~/$REMOTE_DIR/.env"
ENDSSH
