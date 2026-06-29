#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

# Ensure APP_KEY is set before any artisan command runs.
# Auto-generates for dev/local; fails fast in production (never silently regenerate).
source /var/www/html/dockerfiles/lib/ensure-app-key.sh
ensure_app_key

# ─── First-time setup ───────────────────────────────────────────────
if [ ! -f "$LOCK_FILE" ]; then

    # Check if database already has data (lock file deleted but DB intact)
    DB_INITIALIZED=0
    if php artisan migrate:status --no-interaction >/dev/null 2>&1; then
        DB_INITIALIZED=1
    fi

    if [ "$DB_INITIALIZED" -eq 1 ]; then
        echo "→ Existing database detected. Recreating lock file..."
        echo "→ Checking for pending migrations..."
        php artisan migrate --force --no-interaction 2>/dev/null || true
        echo "→ Linking storage..."
        php artisan storage:link 2>/dev/null || true
        touch "$LOCK_FILE"
    else
        echo "══════════════════════════════════════════════"
        echo "  UnoPim: First-time setup"
        echo "══════════════════════════════════════════════"

        echo "→ Installing Composer dependencies..."
        composer install --no-interaction --optimize-autoloader --no-dev

        # Sync APP_URL with APP_PORT if port was changed
        if [ -n "$APP_PORT" ] && [ "$APP_PORT" != "8000" ] && [ -f /var/www/html/.env ]; then
            sed -i "s|APP_URL=http://localhost:8000|APP_URL=http://localhost:${APP_PORT}|" /var/www/html/.env
        fi

        echo "→ Running database migrations..."
        php artisan migrate --force

        echo "→ Seeding database..."
        php artisan db:seed --force

        echo "→ Linking storage..."
        php artisan storage:link 2>/dev/null || true

        # Build Elasticsearch indexes if enabled
        if [ "${ELASTICSEARCH_ENABLED:-false}" = "true" ]; then
            echo "→ Waiting for Elasticsearch to be ready..."
            ES_HOST="${ELASTICSEARCH_HOST:-unopim-elasticsearch:9200}"
            # Normalize: support both "host:port" and "http://host:port"
            case "$ES_HOST" in
                http://*|https://*) ES_URL="$ES_HOST" ;;
                *) ES_URL="http://${ES_HOST}" ;;
            esac
            ES_READY=0
            for i in $(seq 1 30); do
                if curl -sf "${ES_URL}/_cluster/health?wait_for_status=yellow&timeout=5s" >/dev/null 2>&1; then
                    ES_READY=1
                    echo "→ Elasticsearch is ready (yellow or green)."
                    break
                fi
                echo "   Waiting for Elasticsearch... ($i/30)"
                sleep 5
            done

            if [ "$ES_READY" -eq 1 ]; then
                echo "→ Building Elasticsearch indexes..."
                php artisan unopim:product:index --no-interaction 2>/dev/null || true
                php artisan unopim:category:index --no-interaction 2>/dev/null || true
            else
                echo "✗ Elasticsearch did not become ready in time. Failing setup so it can be retried on next container start."
                exit 1
            fi
        fi

        touch "$LOCK_FILE"

        echo "══════════════════════════════════════════════"
        echo "  Setup complete! Open http://localhost:${APP_PORT:-8000}/admin"
        echo "══════════════════════════════════════════════"
    fi
else
    # Lock file exists — check if DB is still intact
    if php artisan migrate:status --no-interaction >/dev/null 2>&1; then
        echo "→ Checking for pending migrations..."
        php artisan migrate --force --no-interaction
    else
        echo ""
        echo "══════════════════════════════════════════════"
        echo "  WARNING: Database is empty but lock file exists."
        echo "  This usually happens after 'docker compose down -v'"
        echo "  which removes all data volumes."
        echo ""
        echo "  TIP: Use 'docker compose down' (without -v) to"
        echo "  preserve your data. Only use -v when you want a"
        echo "  complete reset."
        echo ""
        echo "  Re-running first-time setup..."
        echo "══════════════════════════════════════════════"
        echo ""
        rm -f "$LOCK_FILE"
        exec "$0" "$@"
    fi
fi

# ─── Seal the installer ─────────────────────────────────────────────
INSTALLED_MARKER="/var/www/html/storage/installed"
if [ ! -f "$INSTALLED_MARKER" ]; then
    echo "Your UnoPim App is Successfully Installed" > "$INSTALLED_MARKER"
fi

# Ensure storage directories are writable by the LSPHP worker user (nobody)
chown -R nobody:nogroup /var/www/html/storage /var/www/html/bootstrap/cache

# Clear compiled views (may be stale from previous container)
php artisan view:clear 2>/dev/null || true

# ─── TLS certificate (generated at runtime, never baked into the image) ─────
# HTTP/3 mandates TLS. Generate a dev self-signed cert only when none is
# present, so each container gets a unique key and no private key ships in the
# image layers. Mount real certs over /usr/local/lsws/conf/cert to override.
CERT_DIR=/usr/local/lsws/conf/cert
if [ ! -s "$CERT_DIR/server.key" ] || [ ! -s "$CERT_DIR/server.crt" ]; then
    echo "→ Generating self-signed TLS certificate (development only)..."
    mkdir -p "$CERT_DIR"
    openssl req -x509 -nodes -days 825 -newkey rsa:2048 \
        -keyout "$CERT_DIR/server.key" \
        -out    "$CERT_DIR/server.crt" \
        -subj "/CN=localhost" >/dev/null 2>&1
    chmod 600 "$CERT_DIR/server.key"
    chown -R nobody:nogroup "$CERT_DIR"
fi

# ─── Start OpenLiteSpeed ────────────────────────────────────────────
# OLS daemonizes, so start it then hold PID 1 here. Forward container stop
# signals to `lswsctrl stop` so `docker stop` shuts LiteSpeed down
# gracefully (draining connections) instead of letting it be SIGKILLed.
shutdown() {
    echo "→ Stopping OpenLiteSpeed..."
    /usr/local/lsws/bin/lswsctrl stop 2>/dev/null || true
    exit 0
}
trap shutdown TERM INT

/usr/local/lsws/bin/lswsctrl start

# Hold PID 1 alive while the master runs. The sleep is backgrounded and
# waited on so an incoming TERM/INT interrupts it immediately instead of
# blocking until the 5s elapses. Exit non-zero if the master dies so
# restart:unless-stopped recovers it.
while /usr/local/lsws/bin/lswsctrl status >/dev/null 2>&1; do
    sleep 5 &
    # `|| true` so an unexpected non-zero wait (e.g. a stray signal) under
    # `set -e` doesn't kill PID 1 while the OLS master is still healthy.
    wait $! || true
done

echo "✗ OpenLiteSpeed master process exited." >&2
exit 1
