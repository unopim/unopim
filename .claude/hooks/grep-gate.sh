#!/usr/bin/env bash
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FILE="$(bash "$HERE/lib/payload.sh")"

[ -n "$FILE" ] || exit 0
[ -f "$FILE" ] || exit 0

case "$FILE" in
    *.css)
        if [ "${FILE##*/packages/Webkul/Admin/src/Resources/assets/css/}" = "app.css" ]; then
            :
        elif ! git -C "$(dirname "$FILE")" ls-files --error-unmatch "$FILE" >/dev/null 2>&1; then
            echo "NEW_CSS_FILE: New stylesheets bypass the token system. Use Tailwind classes, or extend the tokens in app.css." >&2
            exit 2
        fi
        ;;
esac

ADDED="$(bash "$HERE/lib/added-lines.sh" "$FILE")"
[ -n "$ADDED" ] || exit 0

FOUND=""
while IFS='~' read -r id path_re content_re message; do
    case "$id" in ''|'#'*) continue ;; esac
    printf '%s' "$FILE" | grep -qP "$path_re" || continue
    hits="$(printf '%s\n' "$ADDED" | grep -nP "$content_re" || true)"
    [ -n "$hits" ] || continue
    FOUND="${FOUND}
${id}: ${message}
$(printf '%s\n' "$hits" | head -5 | sed 's/^/    /')"
done < "$HERE/rules.txt"

if [ -n "$FOUND" ]; then
    {
        printf 'grep-gate blocked %s\n' "$FILE"
        printf '%s\n' "$FOUND"
        printf '\nThese rules apply to the lines this edit added. Pre-existing\n'
        printf 'violations elsewhere in the file are not your problem — fix only\n'
        printf 'what you just wrote. Use the x-admin:: component catalog and the\n'
        printf 'semantic colour tokens (primary, success, warning, danger, info).\n'
    } >&2
    exit 2
fi

exit 0
