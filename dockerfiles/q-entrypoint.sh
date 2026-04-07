#!/bin/bash
set -e

QUEUE_NAMES="${QUEUE_NAMES:-system,completeness,default}"
QUEUE_TIMEOUT="${QUEUE_TIMEOUT:-90}"
QUEUE_TRIES="${QUEUE_TRIES:-3}"
QUEUE_MAX_JOBS="${QUEUE_MAX_JOBS:-1000}"
QUEUE_MAX_TIME="${QUEUE_MAX_TIME:-3600}"

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
