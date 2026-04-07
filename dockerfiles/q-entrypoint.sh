#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

# Queue configuration (override via docker-compose environment)
QUEUE_NAMES="${QUEUE_NAMES:-system,completeness,default}"
QUEUE_TIMEOUT="${QUEUE_TIMEOUT:-90}"
QUEUE_TRIES="${QUEUE_TRIES:-3}"
QUEUE_MAX_JOBS="${QUEUE_MAX_JOBS:-1000}"
QUEUE_MAX_TIME="${QUEUE_MAX_TIME:-3600}"

# Wait for web container to finish first-time setup
echo "Waiting for application setup to complete..."
while [ ! -f "$LOCK_FILE" ]; do
    sleep 5
done
echo "Application ready."

echo "Starting queue worker: queues=${QUEUE_NAMES}"

# queue:work is production-grade (single boot, reuses app state)
# --max-jobs/--max-time auto-restart the worker to prevent memory leaks
exec php artisan queue:work \
    --queue="${QUEUE_NAMES}" \
    --timeout="${QUEUE_TIMEOUT}" \
    --tries="${QUEUE_TRIES}" \
    --max-jobs="${QUEUE_MAX_JOBS}" \
    --max-time="${QUEUE_MAX_TIME}" \
    --sleep=3
