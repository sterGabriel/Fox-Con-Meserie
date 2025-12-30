#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEFAULT_PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

PROJECT_DIR="${1:-${PROJECT_DIR:-$DEFAULT_PROJECT_DIR}}"
SYSTEMD_DIR="/etc/systemd/system"

SERVICE_SRC="$PROJECT_DIR/scripts/systemd/iptv-panel-github-backup.service"
TIMER_SRC="$PROJECT_DIR/scripts/systemd/iptv-panel-github-backup.timer"
SERVICE_DST="$SYSTEMD_DIR/iptv-panel-github-backup.service"
TIMER_DST="$SYSTEMD_DIR/iptv-panel-github-backup.timer"

if [[ $EUID -ne 0 ]]; then
  echo "Run as root: sudo $0 [PROJECT_DIR]" >&2
  exit 1
fi

if [[ ! -d "$PROJECT_DIR" ]]; then
  echo "ERROR: Project dir not found: $PROJECT_DIR" >&2
  exit 1
fi

render_unit() {
  local src="$1"
  local dst="$2"
  sed "s|__IPTV_PANEL_DIR__|$PROJECT_DIR|g" "$src" > "$dst"
}

if [[ ! -f "$SERVICE_SRC" || ! -f "$TIMER_SRC" ]]; then
  echo "ERROR: Missing unit templates in repo (expected $SERVICE_SRC and $TIMER_SRC)" >&2
  exit 1
fi

echo "Installing GitHub backup units..."
render_unit "$SERVICE_SRC" "$SERVICE_DST"
render_unit "$TIMER_SRC" "$TIMER_DST"

echo "Reloading systemd..."
systemctl daemon-reload

echo "Enabling + starting timer..."
systemctl enable --now iptv-panel-github-backup.timer

echo "Done. Check status: systemctl status iptv-panel-github-backup.timer"
echo "Run once now: systemctl start iptv-panel-github-backup.service"
