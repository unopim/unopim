#!/bin/bash
set -e

ROOT=/var/www/html

source "${ROOT}/dockerfiles/lib/ensure-vendor.sh"
source "${ROOT}/dockerfiles/lib/ensure-app-key.sh"
source "${ROOT}/dockerfiles/lib/wait-for-db.sh"
source "${ROOT}/dockerfiles/lib/setup-app.sh"

# Order matters: nothing may call artisan before the autoloader exists and
# APP_KEY resolves.
ensure_vendor "$ROOT"
ensure_app_key
wait_for_db
setup_app "$ROOT"

chown -R www-data:www-data "${ROOT}/storage" "${ROOT}/bootstrap/cache"

php artisan view:clear --no-interaction >/dev/null 2>&1 || true

echo "→ UnoPim ready on ${APP_URL:-http://localhost:8000}/${APP_ADMIN_URL:-admin}"

exec apache2-foreground
