#!/bin/bash
set -e

LOCK_FILE="/var/www/html/storage/unopim.lock"

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

        # Generate APP_KEY if not already set in .env
        if grep -q "^APP_KEY=$" /var/www/html/.env 2>/dev/null || [ -z "$APP_KEY" ]; then
            echo "→ Generating application key..."
            php artisan key:generate --force
            # Export into current process so PHP-FPM inherits the new key
            export APP_KEY=$(grep "^APP_KEY=" /var/www/html/.env | cut -d '=' -f 2-)
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

# Ensure storage directories are writable
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Clear compiled views (may be stale from previous container)
php artisan view:clear 2>/dev/null || true

# Start PHP-FPM (master runs as root, workers run as www-data via www.conf)
exec php-fpm
