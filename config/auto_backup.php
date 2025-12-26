<?php

return [
    // Enable automatic DB backup before POST/PUT/PATCH/DELETE requests.
    'enabled' => (bool) env('AUTO_DB_BACKUP_ENABLED', true),

    // Throttle backups so we don't create one per click.
    'min_interval_seconds' => (int) env('AUTO_DB_BACKUP_MIN_INTERVAL', 300),

    // mysqldump binary name/path.
    'mysqldump_bin' => (string) env('MYSQLDUMP_BIN', 'mysqldump'),

    // Safety timeout for mysqldump.
    'timeout_seconds' => (int) env('AUTO_DB_BACKUP_TIMEOUT', 60),
];
