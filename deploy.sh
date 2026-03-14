#!/usr/bin/env bash
set -euo pipefail

cd /var/www/tihamafarm.xrdlab.io

echo "[deploy] preflight checks..."
[ -f .env ] || { echo "[deploy] .env missing"; exit 1; }
[ -f database/database.sqlite ] || { echo "[deploy] database.sqlite missing"; exit 1; }
[ -r .env ] || { echo "[deploy] .env not readable"; exit 1; }
[ -w database/database.sqlite ] || { echo "[deploy] database.sqlite not writable"; exit 1; }
[ -w database ] || { echo "[deploy] database directory not writable"; exit 1; }
grep -q '^APP_KEY=base64:' .env || { echo "[deploy] APP_KEY missing in .env"; exit 1; }

export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] || { echo "[deploy] nvm.sh missing"; exit 1; }
. "$NVM_DIR/nvm.sh"
command -v nvm >/dev/null || { echo "[deploy] nvm not available"; exit 1; }
nvm use 20 >/dev/null

# Always bring app back up if anything fails
cleanup() {
  php artisan up || true
}
trap cleanup EXIT

echo "[deploy] enabling maintenance mode..."
php artisan down || true

echo "[deploy] pulling latest code..."
git fetch origin
git checkout main
git pull --ff-only origin main

echo "[deploy] installing dependencies..."
composer install --no-dev --optimize-autoloader
npm ci
npm run build

echo "[deploy] creating sqlite backup..."
DB_FILE="/var/www/tihamafarm.xrdlab.io/database/database.sqlite"
BACKUP_DIR="/var/www/tihamafarm.xrdlab.io/database/backups"
TS="$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"
cp "$DB_FILE" "$BACKUP_DIR/database-$TS.sqlite"
echo "[deploy] backup created: $BACKUP_DIR/database-$TS.sqlite"

echo "[deploy] applying migrations and caches..."
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

echo "[deploy] reloading apache..."
sudo -n /bin/systemctl reload apache2

echo "[deploy] done"
