#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

# Wait for web container to finish first-time setup
echo "Waiting for application setup to complete..."
while [ ! -f "$LOCK_FILE" ]; do
    sleep 5
done
echo "Application ready."

echo "Starting Laravel scheduler (runs every minute)..."

# Run schedule:work which handles the cron internally
exec php artisan schedule:work --no-interaction
