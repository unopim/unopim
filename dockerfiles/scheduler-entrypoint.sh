#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

# Wait for web container to finish first-time setup (with timeout)
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

echo "Starting Laravel scheduler (runs every minute)..."

# Run schedule:work which handles the cron internally
exec php artisan schedule:work --no-interaction
