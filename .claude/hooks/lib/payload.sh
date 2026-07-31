#!/usr/bin/env bash
set -euo pipefail

python3 -c '
import json, sys
try:
    data = json.load(sys.stdin)
except Exception:
    sys.exit(0)
inp = data.get("tool_input") or {}
print(inp.get("file_path") or inp.get("path") or "")
'
