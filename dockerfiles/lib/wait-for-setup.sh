#!/bin/bash
# Blocks until the application container has finished migrating and seeding, for
# orchestrators that cannot gate on its health check.

wait_for_setup() {
    local root="${1:-/var/www/html}"
    local lock_file="${root}/storage/unopim.lock"
    local timeout="${SETUP_WAIT_TIMEOUT:-300}"
    local elapsed=0

    if [ -f "$lock_file" ]; then
        return 0
    fi

    echo "→ Waiting for the application container to finish setup..."

    while [ ! -f "$lock_file" ]; do
        if [ "$elapsed" -ge "$timeout" ]; then
            echo "✗ Setup did not complete within ${timeout}s (${lock_file} never appeared)." >&2
            echo "  Check the application container's logs." >&2

            return 1
        fi

        sleep 5
        elapsed=$((elapsed + 5))
    done
}
