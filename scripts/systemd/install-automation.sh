#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/iptv-panel"
SYSTEMD_DIR="/etc/systemd/system"

SCHEDULE_UNIT_SRC="$PROJECT_DIR/scripts/systemd/iptv-panel-schedule.service"
QUEUE_UNIT_SRC="$PROJECT_DIR/scripts/systemd/iptv-panel-queue.service"
AUTOSTART_UNIT_SRC="$PROJECT_DIR/scripts/systemd/iptv-panel-autostart.service"

SCHEDULE_UNIT_DST="$SYSTEMD_DIR/iptv-panel-schedule.service"
QUEUE_UNIT_DST="$SYSTEMD_DIR/iptv-panel-queue.service"
AUTOSTART_UNIT_DST="$SYSTEMD_DIR/iptv-panel-autostart.service"

if [[ $EUID -ne 0 ]]; then
  echo "ERROR: Run as root (use sudo)."
  exit 1
fi

if [[ ! -f "$SCHEDULE_UNIT_SRC" ]]; then
  echo "ERROR: Missing $SCHEDULE_UNIT_SRC"
  exit 1
fi

if [[ ! -f "$QUEUE_UNIT_SRC" ]]; then
  echo "ERROR: Missing $QUEUE_UNIT_SRC"
  exit 1
fi

if [[ ! -f "$AUTOSTART_UNIT_SRC" ]]; then
  echo "ERROR: Missing $AUTOSTART_UNIT_SRC"
  exit 1
fi

echo "Installing systemd units..."
cp -f "$SCHEDULE_UNIT_SRC" "$SCHEDULE_UNIT_DST"
cp -f "$QUEUE_UNIT_SRC" "$QUEUE_UNIT_DST"
cp -f "$AUTOSTART_UNIT_SRC" "$AUTOSTART_UNIT_DST"

echo "Ensuring writable storage/cache for www-data..."
chown -R www-data:www-data "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache" || true
chmod -R u+rwX,g+rwX "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache" || true

systemctl daemon-reload

echo "Enabling and starting services..."
systemctl enable --now iptv-panel-schedule.service
systemctl enable --now iptv-panel-queue.service
systemctl enable --now iptv-panel-autostart.service

echo
echo "OK. Services installed and started."
echo "- Check schedule logs: journalctl -u iptv-panel-schedule.service -f"
echo "- Check queue logs:     journalctl -u iptv-panel-queue.service -f"
echo "- Check autostart logs: journalctl -u iptv-panel-autostart.service -f"
