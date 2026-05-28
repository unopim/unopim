#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

# Wait for app server to finish first-time setup (with timeout)
SETUP_WAIT_TIMEOUT="${SETUP_WAIT_TIMEOUT:-300}"
elapsed=0

echo "Waiting for application setup to complete..."
while [ ! -f "$LOCK_FILE" ]; do
    if [ "$elapsed" -ge "$SETUP_WAIT_TIMEOUT" ]; then
        echo "Error: Timed out after ${SETUP_WAIT_TIMEOUT}s waiting for setup. Lock file not found: ${LOCK_FILE}" >&2
        exit 1
    fi
    sleep 5
    elapsed=$((elapsed + 5))
done
echo "Application ready."

# Pick up APP_KEY from .env (fpm container wrote it after we started).
export APP_KEY=$(grep '^APP_KEY=' /var/www/html/.env | cut -d= -f2-)

echo "Starting Laravel scheduler (runs every minute)..."

# Drop to www-data and start scheduler
exec gosu www-data php artisan schedule:work --no-interaction
