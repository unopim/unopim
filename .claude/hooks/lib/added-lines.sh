#!/usr/bin/env bash
set -euo pipefail

FILE="$1"
DIR="$(dirname "$FILE")"

if ! git -C "$DIR" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    grep -h '' "$FILE" 2>/dev/null || true
    exit 0
fi

if git -C "$DIR" ls-files --error-unmatch "$FILE" >/dev/null 2>&1; then
    git -C "$DIR" diff -U0 --no-color -- "$FILE" \
        | sed -n 's/^+\([^+].*\)/\1/p'
else
    grep -h '' "$FILE" 2>/dev/null || true
fi
