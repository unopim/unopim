#!/bin/bash
# Resolves Laravel's APP_KEY for a container, in order of precedence:
#
#   1. APP_KEY in the environment (injected secret — always wins)
#   2. APP_KEY in .env (source checkout / development stack)
#   3. A key persisted to the storage volume by an earlier boot
#   4. A freshly generated key, persisted for every later boot
#
# Step 4 is what lets the published image run with no configuration at all. The
# key is written once and reused, so encrypted data stays readable across
# restarts and image upgrades — the failure the old production guard existed to
# prevent. Losing the storage volume with no APP_KEY in the environment is still
# unrecoverable, which is why production is told to inject one.
#
# Idempotent: safe to source on every container start.

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

    # Generated without booting Laravel so this still works before the
    # autoloader exists. Same shape as key:generate: 32 random bytes, base64.
    APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    export APP_KEY

    ( umask 077 && printf '%s' "$APP_KEY" > "$key_file" )

    # Keep a source checkout's .env in step so host-side artisan agrees.
    if [ -f "$env_file" ] && [ -w "$env_file" ]; then
        if grep -qE '^APP_KEY=' "$env_file"; then
            sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" "$env_file"
        else
            echo "APP_KEY=${APP_KEY}" >> "$env_file"
        fi
    fi
}
