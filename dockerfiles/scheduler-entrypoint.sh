#!/bin/bash
set -e

ROOT=/var/www/html

source "${ROOT}/dockerfiles/lib/ensure-app-key.sh"
source "${ROOT}/dockerfiles/lib/wait-for-setup.sh"

wait_for_setup "$ROOT"
ensure_app_key

echo "→ Starting scheduler"

exec gosu www-data php artisan schedule:work --no-interaction
