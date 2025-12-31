#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="${PROJECT_DIR:-/var/www/iptv-panel}"
STATUS_FILE="${STATUS_FILE:-$PROJECT_DIR/storage/app/github-backup/status.json}"
INTERVAL="${INTERVAL:-1}"

WIDTH="${WIDTH:-40}"

export STATUS_FILE

read_status() {
  if [[ ! -f "$STATUS_FILE" ]]; then
    echo "0|waiting for status file..."
    return 0
  fi

  php -r '
    $p = getenv("STATUS_FILE");
    $raw = @file_get_contents($p);
    if ($raw === false) { echo "0|cannot read status file"; exit(0); }
    $d = json_decode($raw, true);
    if (!is_array($d)) { echo "0|invalid status json"; exit(0); }
    $pct = isset($d["percent"]) ? (int)$d["percent"] : 0;
    $msg = isset($d["message"]) ? (string)$d["message"] : "";
    $pct = max(0, min(100, $pct));
    $msg = str_replace(["\r","\n"], [" "," "], $msg);
    echo $pct . "|" . $msg;
  ' 2>/dev/null || echo "0|php error reading status"
}

render_bar() {
  local pct="$1"
  local msg="$2"

  local filled=$(( pct * WIDTH / 100 ))
  local empty=$(( WIDTH - filled ))

  printf '\r['
  if [[ "$filled" -gt 0 ]]; then
    printf '%0.s#' $(seq 1 "$filled") 2>/dev/null || true
  fi
  if [[ "$empty" -gt 0 ]]; then
    printf '%0.s-' $(seq 1 "$empty") 2>/dev/null || true
  fi
  printf '] %3s%% %s' "$pct" "$msg"
}

while true; do
  IFS='|' read -r pct msg < <(read_status)
  render_bar "$pct" "$msg"
  if [[ "$pct" == "100" ]]; then
    printf '\n'
    exit 0
  fi
  sleep "$INTERVAL"
done
