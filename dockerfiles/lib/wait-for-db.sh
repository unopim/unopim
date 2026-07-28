#!/bin/bash
# Waits for the configured database engine to accept TCP connections.
#
# Compose health-gates the DB container, but only the engine selected by
# COMPOSE_PROFILES is started. A DB_CONNECTION/COMPOSE_PROFILES mismatch
# therefore leaves the app pointed at a host that will never resolve — this
# turns that into an explicit, actionable failure instead of a stack trace
# from the first artisan command.
#
# Idempotent: safe to source on every container start.

wait_for_db() {
    local host="${DB_HOST:-unopim-pgsql}"
    local port="${DB_PORT:-5432}"
    local timeout="${DB_WAIT_TIMEOUT:-120}"
    local elapsed=0

    echo "→ Waiting for database at ${host}:${port}..."

    while ! (exec 3<>"/dev/tcp/${host}/${port}") 2>/dev/null; do
        if [ "$elapsed" -ge "$timeout" ]; then
            echo "✗ Database ${host}:${port} unreachable after ${timeout}s." >&2
            echo "  DB_CONNECTION=${DB_CONNECTION:-unset} must match the engine started by" >&2
            echo "  COMPOSE_PROFILES in .env:" >&2
            echo "    pgsql -> COMPOSE_PROFILES=pgsql, DB_HOST=unopim-pgsql, DB_PORT=5432" >&2
            echo "    mysql -> COMPOSE_PROFILES=mysql, DB_HOST=unopim-mysql, DB_PORT=3306" >&2
            return 1
        fi
        sleep 3
        elapsed=$((elapsed + 3))
    done

    exec 3<&- 2>/dev/null || true
    echo "→ Database is reachable."
}
