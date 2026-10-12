#!/bin/bash
# Resolves APP_KEY from the environment, then .env, then a key persisted to the
# storage volume, generating and persisting one only as a last resort so an
# existing key is never replaced.

ensure_app_key() {
    local env_file="${1:-/var/www/html/.env}"
    local key_file="${UNOPIM_APP_KEY_FILE:-/var/www/html/storage/app/private/.app_key}"

    if [[ "${APP_KEY:-}" == base64:* ]]; then
        return 0
    fi

    if [ -f "$env_file" ] && grep -qE '^APP_KEY=base64:.+' "$env_file"; then
        APP_KEY=$(grep -E '^APP_KEY=' "$env_file" | head -1 | cut -d '=' -f 2-)
        export APP_KEY

        return 0
    fi

    if [ -s "$key_file" ]; then
        APP_KEY=$(cat "$key_file")
        export APP_KEY

        return 0
    fi

    if ! mkdir -p "$(dirname "$key_file")" 2>/dev/null; then
        echo "✗ APP_KEY is not set and $(dirname "$key_file") is not writable." >&2
        echo "  Inject APP_KEY as a secret, or mount a writable storage volume." >&2

        return 1
    fi

    echo "→ APP_KEY not set — generating one and persisting it to ${key_file}"

    APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    export APP_KEY

    ( umask 077 && printf '%s' "$APP_KEY" > "$key_file" )

    if [ -f "$env_file" ] && [ -w "$env_file" ]; then
        if grep -qE '^APP_KEY=' "$env_file"; then
            sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" "$env_file"
        else
            echo "APP_KEY=${APP_KEY}" >> "$env_file"
        fi
    fi
}
