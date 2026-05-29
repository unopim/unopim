#!/bin/bash
# Ensures Laravel APP_KEY is set in .env before any artisan command runs.
#
# Behavior:
#   - APP_KEY already valid  -> no-op
#   - APP_ENV=production     -> fail fast (never auto-regenerate; would destroy
#                               existing encrypted data such as sessions,
#                               password resets, encrypted columns)
#   - Dev/local/staging      -> generate new key
#
# Idempotent: safe to source on every container start.

ensure_app_key() {
    local env_file="${1:-/var/www/html/.env}"

    if [ ! -f "$env_file" ]; then
        echo "✗ .env not found at $env_file" >&2
        return 1
    fi

    if [ ! -w "$env_file" ]; then
        echo "✗ .env not writable at $env_file (check volume mount permissions)" >&2
        return 1
    fi

    # Valid key format: APP_KEY=base64:<non-empty-value>
    if grep -qE "^APP_KEY=base64:.+" "$env_file"; then
        return 0
    fi

    if [ "${APP_ENV:-local}" = "production" ]; then
        echo "✗ APP_KEY is missing or invalid in production." >&2
        echo "  Production deployments must inject APP_KEY via secret management" >&2
        echo "  (Docker secret, Kubernetes Secret, Vault, etc.) — never auto-generate." >&2
        echo "  Auto-generating would invalidate all existing encrypted data." >&2
        return 1
    fi

    echo "→ APP_KEY missing or empty — generating new key (non-production)..."

    # Ensure APP_KEY line exists so key:generate has a target to update.
    grep -q "^APP_KEY=" "$env_file" || echo "APP_KEY=" >> "$env_file"

    php artisan key:generate --force --no-interaction
    php artisan config:clear --no-interaction >/dev/null 2>&1 || true

    # Re-export so the current shell + child processes (PHP-FPM master) inherit it.
    export APP_KEY=$(grep "^APP_KEY=" "$env_file" | cut -d '=' -f 2-)
}
