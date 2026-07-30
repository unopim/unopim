#!/bin/bash
# Brings the application up to date: migrations, first-run seed, storage link
# and search indexes.
#
# Only the application container runs this. Queue and scheduler containers wait
# for it via a health gate, so nothing else writes the schema concurrently.
#
# Installation state is read back from the database rather than a marker file,
# so a wiped volume or a wiped database recovers on the next boot instead of
# deadlocking on a stale lock file.
#
# Set UNOPIM_SKIP_MIGRATIONS=true where schema changes belong to the deployment
# itself — a Kubernetes Job, a Helm pre-upgrade hook, a CI release step. Running
# migrations from every replica of a scaled Deployment races the same schema.

setup_app() {
    local root="${1:-/var/www/html}"
    local lock_file="${root}/storage/unopim.lock"

    if [ "${UNOPIM_SKIP_MIGRATIONS:-false}" = "true" ]; then
        echo "→ UNOPIM_SKIP_MIGRATIONS=true — schema is managed outside the container."
        touch "$lock_file"

        return 0
    fi

    if php artisan migrate:status --no-interaction >/dev/null 2>&1; then
        echo "→ Applying pending migrations..."
        php artisan migrate --force --no-interaction
    else
        echo "→ First-time setup — migrating and seeding..."
        php artisan migrate --force --no-interaction
        php artisan db:seed --force --no-interaction
    fi

    php artisan storage:link --no-interaction >/dev/null 2>&1 || true

    if [ "${ELASTICSEARCH_ENABLED:-false}" = "true" ] && [ ! -f "$lock_file" ]; then
        _wait_for_elasticsearch || return 1

        echo "→ Building search indexes..."
        php artisan unopim:product:index --no-interaction || true
        php artisan unopim:category:index --no-interaction || true
    fi

    echo 'Your UnoPim App is Successfully Installed' > "${root}/storage/installed"
    touch "$lock_file"
}

_wait_for_elasticsearch() {
    local host="${ELASTICSEARCH_HOST:-unopim-elasticsearch:9200}"
    local url

    case "$host" in
        http://*|https://*) url="$host" ;;
        *) url="http://${host}" ;;
    esac

    echo "→ Waiting for Elasticsearch at ${url}..."

    for _ in $(seq 1 30); do
        if curl -fsS "${url}/_cluster/health?wait_for_status=yellow&timeout=5s" >/dev/null 2>&1; then
            return 0
        fi

        sleep 5
    done

    echo "✗ Elasticsearch did not become ready. Set ELASTICSEARCH_ENABLED=false to skip indexing." >&2

    return 1
}
