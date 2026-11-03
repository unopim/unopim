#!/bin/bash
# Migrates, seeds on first run, links storage and builds search indexes. Only
# the application container runs this. Set UNOPIM_SKIP_MIGRATIONS=true where the
# deployment owns the schema, since scaled replicas would otherwise race it.
#
# Signing keys are created ahead of that flag: they are not schema, so a
# deployment managing migrations elsewhere still needs a pair, and the workers
# read this one from the shared storage volume.

setup_app() {
    local root="${1:-/var/www/html}"
    local lock_file="${root}/storage/unopim.lock"

    echo "→ Ensuring API signing keys..."
    php artisan unopim:passport:keys --no-interaction || return 1

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

    php artisan storage:link --relative --no-interaction >/dev/null 2>&1 || true

    if [ "${ELASTICSEARCH_ENABLED:-false}" = "true" ] && [ ! -f "$lock_file" ]; then
        _wait_for_elasticsearch || return 1

        echo "→ Building search indexes..."
        php artisan unopim:product:index --no-interaction || true
        php artisan unopim:category:index --no-interaction || true
    fi

    echo 'Your UnoPim App is Successfully Installed' > "${root}/storage/installed"
    touch "$lock_file"
}

# Waits for at least yellow cluster health so the first index build does not
# race shard allocation. A least-privilege user may lack cluster:monitor/health
# and get 401/403 there; the probe then falls back to GET /, which only needs
# cluster:monitor/main. Each request is time-boxed so the wait stays bounded,
# and the logged URL omits any userinfo.
_wait_for_elasticsearch() {
    local host="${ELASTICSEARCH_HOST:-unopim-elasticsearch:9200}"
    local url status

    case "$host" in
        http://*|https://*) url="$host" ;;
        *) url="http://${host}" ;;
    esac

    echo "→ Waiting for Elasticsearch at $(printf '%s' "$url" | sed -E 's#^(https?://)[^/]*@#\1#')..."

    for _ in $(seq 1 30); do
        status=$(_elasticsearch_curl_auth | curl -K - --connect-timeout 5 --max-time 15 -sS -o /dev/null -w '%{http_code}' \
            "${url}/_cluster/health?wait_for_status=yellow&timeout=5s" 2>/dev/null) || true

        case "$status" in
            200) return 0 ;;
            401|403)
                if _elasticsearch_curl_auth | curl -K - --connect-timeout 5 --max-time 15 -fsS -o /dev/null "${url}/" >/dev/null 2>&1; then
                    return 0
                fi
                ;;
        esac

        sleep 5
    done

    echo "✗ Elasticsearch did not become ready (last HTTP status: ${status:-none}). Set ELASTICSEARCH_ENABLED=false to skip indexing." >&2

    return 1
}

# Prints a curl config with the credentials the app connects with: the API key
# when it is set for the api or cloud connection, otherwise ELASTICSEARCH_USER/PASS.
# It is piped to curl -K - so ELASTICSEARCH_USER, ELASTICSEARCH_PASS and
# ELASTICSEARCH_API_KEY never appear in a process argument list.
_elasticsearch_curl_auth() {
    if [ -n "${ELASTICSEARCH_API_KEY:-}" ] && [ "${ELASTICSEARCH_CONNECTION:-default}" != "default" ]; then
        printf 'header = "Authorization: ApiKey %s"\n' "$(_curl_config_escape "$ELASTICSEARCH_API_KEY")"
    elif [ -n "${ELASTICSEARCH_USER:-}" ]; then
        printf 'user = "%s:%s"\n' "$(_curl_config_escape "$ELASTICSEARCH_USER")" "$(_curl_config_escape "${ELASTICSEARCH_PASS:-}")"
    fi
}

# Escapes a value for a quoted curl config string: backslashes first, then
# double quotes, newlines and carriage returns, so a value cannot end the
# string or start a new config directive.
_curl_config_escape() {
    local value="${1//\\/\\\\}"

    value="${value//\"/\\\"}"
    value="${value//$'\n'/\\n}"
    value="${value//$'\r'/\\r}"

    printf '%s' "$value"
}
