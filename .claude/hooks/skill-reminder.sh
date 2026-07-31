#!/usr/bin/env bash
set -euo pipefail

python3 -c '
import json

print(json.dumps({
    "hookSpecificOutput": {
        "hookEventName": "UserPromptSubmit",
        "additionalContext": (
            "UnoPim skills are NOT auto-loaded — invoke them with the Skill tool.\n"
            "- Before writing or changing any PHP, Blade, JS or Vue: invoke `unopim-standards`.\n"
            "- Before running artisan, composer, pest or npm: invoke `unopim-exec`.\n"
            "- Before calling a change done: invoke `unopim-verify`.\n"
            "The skill body outranks any summary of it: no comments inside method bodies, "
            "array literals or Blade markup — a non-obvious rationale belongs in the "
            "class/method PHPDoc or the commit message."
        ),
    }
}))
'
