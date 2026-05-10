#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

# Queue configuration (override via docker-compose environment)
QUEUE_NAMES="${QUEUE_NAMES:-system,completeness,default}"
QUEUE_TIMEOUT="${QUEUE_TIMEOUT:-90}"
QUEUE_TRIES="${QUEUE_TRIES:-3}"
QUEUE_MAX_JOBS="${QUEUE_MAX_JOBS:-1000}"
QUEUE_MAX_TIME="${QUEUE_MAX_TIME:-3600}"

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

echo "Starting queue worker: queues=${QUEUE_NAMES}"

# Drop to www-data and start queue:work
# --max-jobs/--max-time auto-restart the worker to prevent memory leaks
exec gosu www-data php artisan queue:work \
    --queue="${QUEUE_NAMES}" \
    --timeout="${QUEUE_TIMEOUT}" \
    --tries="${QUEUE_TRIES}" \
    --max-jobs="${QUEUE_MAX_JOBS}" \
    --max-time="${QUEUE_MAX_TIME}" \
    --sleep=3
