#!/bin/bash
set -e

ROOT=/var/www/html

source "${ROOT}/dockerfiles/lib/ensure-app-key.sh"
source "${ROOT}/dockerfiles/lib/wait-for-setup.sh"

QUEUE_NAMES="${QUEUE_NAMES:-webhooks,system,completeness,default,publication}"
QUEUE_TIMEOUT="${QUEUE_TIMEOUT:-90}"
QUEUE_TRIES="${QUEUE_TRIES:-3}"
QUEUE_MAX_JOBS="${QUEUE_MAX_JOBS:-1000}"
QUEUE_MAX_TIME="${QUEUE_MAX_TIME:-3600}"

wait_for_setup "$ROOT"
ensure_app_key

echo "→ Starting queue worker: ${QUEUE_NAMES}"

# --max-jobs/--max-time recycle the worker so long-lived leaks cannot accumulate.
exec gosu www-data php artisan queue:work \
    --queue="${QUEUE_NAMES}" \
    --timeout="${QUEUE_TIMEOUT}" \
    --tries="${QUEUE_TRIES}" \
    --max-jobs="${QUEUE_MAX_JOBS}" \
    --max-time="${QUEUE_MAX_TIME}" \
    --sleep=3
