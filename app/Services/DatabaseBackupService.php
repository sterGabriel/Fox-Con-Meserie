<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    public function backupIfEnabledAndDue(string $reason = 'request'): array
    {
        if (!config('auto_backup.enabled')) {
            return ['ran' => false, 'skipped' => true, 'reason' => 'disabled'];
        }

        $minInterval = (int) config('auto_backup.min_interval_seconds', 300);
        if ($minInterval < 0) {
            $minInterval = 0;
        }

        $sentinelPath = storage_path('app/db-backups/.last_backup');
        $last = @file_exists($sentinelPath) ? @filemtime($sentinelPath) : false;
        if ($last !== false && (time() - (int) $last) < $minInterval) {
            return ['ran' => false, 'skipped' => true, 'reason' => 'throttled'];
        }

        $res = $this->backup($reason);
        if (($res['ok'] ?? false) === true) {
            @mkdir(dirname($sentinelPath), 0755, true);
            @touch($sentinelPath);
        }

        return $res;
    }

    public function backup(string $reason = 'manual'): array
    {
        $connectionName = (string) config('database.default');
        $conn = config('database.connections.' . $connectionName);

        if (!is_array($conn) || (($conn['driver'] ?? null) !== 'mysql')) {
            return [
                'ok' => false,
                'ran' => false,
                'error' => 'Auto backup supports only MySQL (current: ' . $connectionName . ')',
            ];
        }

        $host = (string) ($conn['host'] ?? '127.0.0.1');
        $port = (string) ($conn['port'] ?? '3306');
        $database = (string) ($conn['database'] ?? '');
        $username = (string) ($conn['username'] ?? '');
        $password = (string) ($conn['password'] ?? '');

        if ($database === '' || $username === '') {
            return [
                'ok' => false,
                'ran' => false,
                'error' => 'Missing DB_DATABASE / DB_USERNAME',
            ];
        }

        $dir = storage_path('app/db-backups');
        @mkdir($dir, 0755, true);

        $ts = date('Ymd_His');
        $safeReason = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $reason) ?: 'backup';
        $file = $dir . DIRECTORY_SEPARATOR . $database . '_' . $ts . '_' . $safeReason . '.sql';

        $bin = (string) config('auto_backup.mysqldump_bin', 'mysqldump');

        $process = new Process([
            $bin,
            '--result-file=' . $file,
            '--single-transaction',
            '--quick',
            '--lock-tables=false',
            '--skip-comments',
            '-h', $host,
            '-P', $port,
            '-u', $username,
            $database,
        ]);

        $process->setTimeout((int) config('auto_backup.timeout_seconds', 60));
        $process->setEnv(array_filter([
            'MYSQL_PWD' => $password,
        ], fn ($v) => $v !== null));

        try {
            $process->mustRun();
        } catch (\Throwable $e) {
            Log::warning('DB backup failed: ' . $e->getMessage(), [
                'reason' => $reason,
                'db' => $database,
                'host' => $host,
                'port' => $port,
            ]);

            return [
                'ok' => false,
                'ran' => true,
                'error' => 'mysqldump failed: ' . $e->getMessage(),
            ];
        }

        if (!is_file($file)) {
            return [
                'ok' => false,
                'ran' => true,
                'error' => 'mysqldump did not create output file',
            ];
        }

        $size = @filesize($file);
        if (!is_int($size) || $size <= 0) {
            return [
                'ok' => false,
                'ran' => true,
                'error' => 'mysqldump created an empty output file',
            ];
        }

        return [
            'ok' => true,
            'ran' => true,
            'path' => $file,
        ];
    }
}
