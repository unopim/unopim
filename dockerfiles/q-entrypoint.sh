#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

QUEUE_NAMES="${QUEUE_NAMES:-system,completeness,default}"
QUEUE_TIMEOUT="${QUEUE_TIMEOUT:-90}"
QUEUE_TRIES="${QUEUE_TRIES:-3}"
QUEUE_MAX_JOBS="${QUEUE_MAX_JOBS:-1000}"
QUEUE_MAX_TIME="${QUEUE_MAX_TIME:-3600}"

# Wait for web container to finish setup (composer install, migrations, etc.)
echo "Waiting for application setup to complete..."
while [ ! -f "$LOCK_FILE" ]; do
    sleep 5
done
echo "Application ready."

echo "Starting queue worker: queues=${QUEUE_NAMES}, timeout=${QUEUE_TIMEOUT}s, tries=${QUEUE_TRIES}"

# Use queue:work (not queue:listen) for production performance
# --max-jobs and --max-time prevent memory leaks from long-running workers
exec php artisan queue:work \
    --queue="${QUEUE_NAMES}" \
    --timeout="${QUEUE_TIMEOUT}" \
    --tries="${QUEUE_TRIES}" \
    --max-jobs="${QUEUE_MAX_JOBS}" \
    --max-time="${QUEUE_MAX_TIME}" \
    --sleep=3
