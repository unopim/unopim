#!/usr/bin/env bash
#
# Withhold every Playwright failure-artifact path that holds an AI provider key.
#
# Usage: scrub-e2e-artifacts.sh <path>...
#
# Each path is searched recursively, including inside zip archives (traces) and
# the zip the HTML report embeds as base64, for OpenAI (sk-) and Groq (gsk_)
# key shapes and for the literal values of OPENAI_API_KEY, GROQ_API_KEY and
# GROQ_INVALID_KEY when they are set. A path that matches, or that cannot be
# fully scanned (a read error, a symlink or other non-regular file, an archive
# nested too deep), is deleted. A match is never printed.
#
# The paths that remain are written to the `paths` step output, so the upload
# step sends only what was scanned. The script exits non-zero when a withheld
# path could not be deleted; the job still fails on the original test failure.

set -uo pipefail

readonly KEY_PATTERN='(^|[^A-Za-z0-9_-])(sk-[A-Za-z0-9_-]{20,}|gsk_[A-Za-z0-9]{20,})'
readonly MAX_DEPTH=4

work_dir=$(mktemp -d "${RUNNER_TEMP:-/tmp}/scrub-e2e.XXXXXX") || exit 1
trap 'rm -rf "$work_dir"' EXIT
chmod 700 "$work_dir" || exit 1

literals="$work_dir/literals"
: > "$literals" || exit 1

for name in OPENAI_API_KEY GROQ_API_KEY GROQ_INVALID_KEY; do
    value="${!name:-}"

    if [[ -n "$value" ]]; then
        printf '%s\n' "$value" >> "$literals" || exit 1
    fi
done

# Exit 0 when the file holds a credential, 1 when it does not, 2 on a read error.
file_has_credential() {
    local status

    grep -aqE "$KEY_PATTERN" "$1"
    status=$?
    [[ "$status" -ne 1 ]] && return $(( status == 0 ? 0 : 2 ))

    [[ -s "$literals" ]] || return 1

    grep -aqFf "$literals" "$1"
    status=$?

    return $(( status == 0 ? 0 : (status == 1 ? 1 : 2) ))
}

# Exit 0 when the zip holds a credential, 1 when it does not, 2 on an error.
zip_has_credential() {
    local zip=$1 depth=$2 extract

    [[ "$depth" -lt "$MAX_DEPTH" ]] || return 2

    extract=$(mktemp -d "$work_dir/zip.XXXXXX") || return 2
    unzip -qq -o "$zip" -d "$extract" >/dev/null 2>&1 || return 2

    tree_has_credential "$extract" $(( depth + 1 ))
}

# Exit 0 when the file or anything packed inside it holds a credential, 1 when
# none does, 2 on an error.
entry_has_credential() {
    local file=$1 depth=$2 status embedded

    file_has_credential "$file"
    status=$?
    [[ "$status" -ne 1 ]] && return "$status"

    if [[ "$(head -c 4 "$file" 2>/dev/null)" == $'PK\x03\x04' ]]; then
        zip_has_credential "$file" "$depth"
        status=$?
        [[ "$status" -ne 1 ]] && return "$status"
    fi

    if grep -aq 'data:application/zip;base64,' "$file"; then
        embedded=$(mktemp "$work_dir/embedded.XXXXXX") || return 2
        grep -aoE 'data:application/zip;base64,[A-Za-z0-9+/=]+' "$file" \
            | sed 's/^data:application\/zip;base64,//' \
            | base64 -d > "$embedded" 2>/dev/null || return 2
        zip_has_credential "$embedded" "$depth"
        status=$?
        [[ "$status" -ne 1 ]] && return "$status"
    fi

    return 1
}

# Exit 0 when any file under the path holds a credential, 1 when none does, 2 on
# an error or on an entry that is neither a regular file nor a directory.
tree_has_credential() {
    local root=$1 depth=$2 file status files

    if [[ -L "$root" ]]; then
        return 2
    fi

    if [[ -f "$root" ]]; then
        entry_has_credential "$root" "$depth"

        return $?
    fi

    [[ -d "$root" ]] || return 2

    if [[ -n "$(find "$root" ! -type d ! -type f -print -quit 2>/dev/null)" ]]; then
        return 2
    fi

    files=$(mktemp "$work_dir/files.XXXXXX") || return 2
    find "$root" -type f -print0 > "$files" 2>/dev/null || return 2

    while IFS= read -r -d '' file; do
        entry_has_credential "$file" "$depth"
        status=$?
        [[ "$status" -ne 1 ]] && return "$status"
    done < "$files"

    return 1
}

exit_code=0
kept=()

for path in "$@"; do
    [[ -e "$path" || -L "$path" ]] || continue

    tree_has_credential "$path" 0
    status=$?

    if [[ "$status" -eq 1 ]]; then
        kept+=("$path")

        continue
    fi

    if ! rm -rf -- "$path"; then
        echo "::error::Could not delete $path, so the failure artifact must not be uploaded."
        exit_code=1

        continue
    fi

    if [[ "$status" -eq 0 ]]; then
        echo "::warning::Withheld $path from the failure artifact because it contains an AI provider credential."
    else
        echo "::warning::Withheld $path from the failure artifact because it could not be fully scanned for credentials."
    fi
done

if [[ -n "${GITHUB_OUTPUT:-}" ]]; then
    delimiter="paths_$(od -An -N8 -tx1 /dev/urandom | tr -d ' \n')"

    {
        echo "paths<<$delimiter"
        if [[ "$exit_code" -eq 0 && "${#kept[@]}" -gt 0 ]]; then
            printf '%s\n' "${kept[@]}"
        fi
        echo "$delimiter"
    } >> "$GITHUB_OUTPUT" || exit 1
fi

exit "$exit_code"
