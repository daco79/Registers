#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# deploy.sh - Deploiement Registers vers o2switch
# Usage :
#   ./scripts/o2-init-db.sh   # une fois, ou apres modification du schema
#   ./deploy.sh
# =============================================================================

SSH_USER="${SSH_USER:-zece2169}"
SSH_HOST="${SSH_HOST:-dark.o2switch.net}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/mailvio_deploy}"
REMOTE_DIR="${REMOTE_DIR:-registers.fr}"
ZIP_NAME="${ZIP_NAME:-registers_deploy.zip}"
LOCAL_DIR="$(cd "$(dirname "$0")" && pwd)"

GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}=== Deploiement Registers -> o2switch ===${NC}"

cd "$LOCAL_DIR"
rm -f "$ZIP_NAME"

echo -e "${BLUE}[1/4] Creation du zip...${NC}"
zip -q -r "$ZIP_NAME" \
  index.php \
  api/ \
  assets/ \
  includes/ \
  -x "**/.DS_Store" \
  -x "**/__pycache__/*" \
  -x "*.pyc" \
  -x ".env" \
  -x ".git/*"

SIZE=$(du -sh "$ZIP_NAME" | cut -f1)
echo -e "${GREEN}OK - $ZIP_NAME cree ($SIZE)${NC}"

echo -e "${BLUE}[2/4] Upload vers o2switch...${NC}"
scp -i "$SSH_KEY" "$ZIP_NAME" "${SSH_USER}@${SSH_HOST}:~/"
echo -e "${GREEN}OK - Upload termine${NC}"

echo -e "${BLUE}[3/4] Decompression dans ~/${REMOTE_DIR}/...${NC}"
ssh -i "$SSH_KEY" "${SSH_USER}@${SSH_HOST}" bash <<ENDSSH
set -euo pipefail
mkdir -p ~/${REMOTE_DIR}
unzip -q -o ~/${ZIP_NAME} -d ~/${REMOTE_DIR}/
rm -f ~/${ZIP_NAME}
ENDSSH
echo -e "${GREEN}OK - Fichiers deployes${NC}"

echo -e "${BLUE}[4/4] Nettoyage local...${NC}"
rm -f "$LOCAL_DIR/$ZIP_NAME"
echo -e "${GREEN}OK - Zip local supprime${NC}"

echo ""
echo -e "${GREEN}=== Deploiement termine ===${NC}"
echo "Dossier distant : ~/${REMOTE_DIR}/"
