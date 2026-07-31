#!/usr/bin/env bash
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FILE="$(bash "$HERE/lib/payload.sh")"

case "$FILE" in *.php) ;; *) exit 0 ;; esac
[ -f "$FILE" ] || exit 0

ROOT="$FILE"
while [ "$ROOT" != "/" ] && [ ! -f "$ROOT/vendor/bin/pint" ]; do
    ROOT="$(dirname "$ROOT")"
done
[ -f "$ROOT/vendor/bin/pint" ] || exit 0

BEFORE="$(cksum < "$FILE")"
(cd "$ROOT" && vendor/bin/pint "$FILE" >/dev/null 2>&1) || exit 0
AFTER="$(cksum < "$FILE")"

[ "$BEFORE" = "$AFTER" ] || echo "pint reformatted $FILE"
exit 0
