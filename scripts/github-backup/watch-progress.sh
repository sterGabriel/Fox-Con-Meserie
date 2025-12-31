#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="${PROJECT_DIR:-/var/www/iptv-panel}"
STATUS_FILE="${STATUS_FILE:-$PROJECT_DIR/storage/app/github-backup/status.json}"
INTERVAL="${INTERVAL:-1}"

if [[ ! -f "$STATUS_FILE" ]]; then
  echo "Status file not found: $STATUS_FILE" >&2
  echo "Tip: run backup once to create it, or set STATUS_FILE." >&2
  exit 1
fi

render_bar() {
  local pct="$1"
  local width=30
  if [[ "$pct" -lt 0 ]]; then pct=0; fi
  if [[ "$pct" -gt 100 ]]; then pct=100; fi

  local filled=$(( (pct * width) / 100 ))
  local empty=$(( width - filled ))

  local bar=""
  for ((i=0; i<filled; i++)); do bar+="#"; done
  for ((i=0; i<empty; i++)); do bar+="-"; done

  printf '[%s] %3d%%' "$bar" "$pct"
}

while true; do
  # Expect JSON like: {"ts":"...","percent":35,"message":"..."}
  read -r ts pct msg < <(
    php -r '$p=getenv("F"); $j=@file_get_contents($p); $d=@json_decode($j,true); if(!is_array($d)){echo "- 0 (invalid)"; exit(0);} $ts=$d["ts"]??"-"; $pct=$d["percent"]??0; $msg=$d["message"]??""; echo $ts." ".$pct." ".$msg;' \
      F="$STATUS_FILE" 2>/dev/null || echo "- 0 (unreadable)"
  )

  # Clear screen and print one-line progress
  printf '\033[2J\033[H'
  printf '%s  ' "$ts"
  render_bar "$pct"
  printf '\n%s\n' "$msg"

  sleep "$INTERVAL"
done
