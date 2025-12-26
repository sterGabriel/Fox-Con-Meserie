#!/bin/bash
# Backup script for iptv_panel database (MySQL)
# Usage: ./backup-db.sh

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="$(dirname "$0")/backups"
DB_NAME="iptv_panel"
DB_USER="iptv_user"
DB_PASS="FoxIptv2024!"
DB_HOST="127.0.0.1"
DB_PORT="3306"

mkdir -p "$BACKUP_DIR"
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/${DB_NAME}_$TIMESTAMP.sql"

if [ $? -eq 0 ]; then
  echo "Backup successful: $BACKUP_DIR/${DB_NAME}_$TIMESTAMP.sql"
else
  echo "Backup failed!"
  exit 1
fi
