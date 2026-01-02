<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class BackupFull extends Command
{
    protected $signature = 'backup:full
        {--dest= : Destination directory (default: storage/app/backups)}
        {--include-db : Attempt to include a DB dump (best-effort)}
        {--include-env : Include .env files (may contain secrets)}
        {--no-git : Do not include git status/diffs/bundle in the backup}
        {--include-vendor : Include vendor/ (can be very large)}
        {--include-node-modules : Include node_modules/ (can be very large)}';

    protected $description = 'Create a full project backup ZIP with a progress bar.';

    public function handle(): int
    {
        $projectRoot = base_path();
        $destDir = (string) ($this->option('dest') ?: storage_path('app/backups'));
        $includeDb = (bool) $this->option('include-db');
        $includeEnv = (bool) $this->option('include-env');
        $noGit = (bool) $this->option('no-git');
        $includeVendor = (bool) $this->option('include-vendor');
        $includeNodeModules = (bool) $this->option('include-node-modules');

        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        if (!is_dir($destDir) || !is_writable($destDir)) {
            $this->error('Destination directory is not writable: ' . $destDir);
            return self::FAILURE;
        }

        $ts = date('Ymd_His');
        $zipPath = rtrim($destDir, '/') . "/iptv-panel_backup_{$ts}.zip";

        $this->info('Backup destination: ' . $zipPath);

        $tempDir = storage_path('app/backups/_tmp_' . $ts);
        @mkdir($tempDir, 0755, true);

        $extraFiles = [];
        if (!$noGit) {
            $extraFiles = array_merge($extraFiles, $this->tryCreateGitArtifacts($tempDir));
        }
        if ($includeDb) {
            $dbDump = $this->tryCreateDbDump($tempDir);
            if ($dbDump) {
                $extraFiles[] = $dbDump;
                $this->line('Included DB dump: ' . basename($dbDump));
            } else {
                $this->warn('DB dump not included (mysqldump missing or DB config unavailable).');
            }
        }

        // Build file list first, so we can show a progress bar with an accurate max.
        // Use a plain recursive iterator with path-based excludes (more predictable than Finder).
        $excludePrefixes = [
            'storage/app/streams/',
            'storage/app/full-backups/',
            'storage/app/db-backups/',
            'storage/app/backups/',
            'storage/logs/',
            'storage/framework/',
            'public/storage/',
            'public/build/',
        ];

        if (!$includeVendor) {
            $excludePrefixes[] = 'vendor/';
        }

        if (!$includeNodeModules) {
            $excludePrefixes[] = 'node_modules/';
        }

        $this->line('Indexing files…');
        $indexBar = $this->output->createProgressBar();
        $indexBar->setFormat(' indexed: %current% | %message%');
        $indexBar->setMessage('Scanning…');
        $indexBar->start();

        $files = [];
        $indexed = 0;

        $rootNorm = rtrim(str_replace('\\', '/', $projectRoot), '/') . '/';
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $fs) {
            /** @var \SplFileInfo $fs */
            $path = $fs->getPathname();
            $pathNorm = str_replace('\\', '/', $path);

            // Compute workspace-relative path for matching.
            $rel = str_starts_with($pathNorm, $rootNorm) ? substr($pathNorm, strlen($rootNorm)) : $pathNorm;

            // Skip VCS metadata.
            if (str_starts_with($rel, '.git/')) {
                continue;
            }

            // Exclude runtime/large directories by prefix.
            $relForPrefix = $rel;
            if ($relForPrefix !== '' && !str_ends_with($relForPrefix, '/')) {
                // keep as-is
            }
            $skip = false;
            foreach ($excludePrefixes as $prefix) {
                if ($relForPrefix === rtrim($prefix, '/')) {
                    $skip = true;
                    break;
                }
                if (str_starts_with($relForPrefix, $prefix)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

            // Exclude .env files by default (secrets). Allow override via --include-env.
            if (!$includeEnv) {
                $base = basename($rel);
                if ($base === '.env' || str_starts_with($base, '.env.')) {
                    continue;
                }
            }

            if ($fs->isDir()) {
                continue;
            }

            if ($fs->isLink()) {
                continue;
            }

            if (!$fs->isFile() || !$fs->isReadable()) {
                continue;
            }

            $files[] = $path;
            $indexed++;

            if (($indexed % 500) === 0) {
                $indexBar->setMessage(basename($rel));
                $indexBar->advance(500);
            }
        }

        $rem = $indexed % 500;
        if ($rem > 0) {
            $indexBar->advance($rem);
        }

        $indexBar->finish();
        $this->newLine(2);

        foreach ($extraFiles as $f) {
            if (is_file($f)) {
                $files[] = $f;
            }
        }

        if (empty($files)) {
            $this->error('No files found to back up.');
            $this->cleanupTempDir($tempDir);
            return self::FAILURE;
        }

        $zip = new ZipArchive();
        $open = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($open !== true) {
            $this->error('Failed to create ZIP: ' . $zipPath);
            $this->cleanupTempDir($tempDir);
            return self::FAILURE;
        }

        $bar = $this->output->createProgressBar(count($files));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $bar->setMessage('Starting…');
        $bar->start();

        $projectRootNorm = rtrim(str_replace('\\', '/', $projectRoot), '/') . '/';
        $tempDirNorm = rtrim(str_replace('\\', '/', $tempDir), '/') . '/';

        $added = 0;
        foreach ($files as $path) {
            $pathNorm = str_replace('\\', '/', $path);
            $localName = null;

            if (str_starts_with($pathNorm, $projectRootNorm)) {
                $localName = substr($pathNorm, strlen($projectRootNorm));
            } elseif (str_starts_with($pathNorm, $tempDirNorm)) {
                $localName = 'backup_meta/' . substr($pathNorm, strlen($tempDirNorm));
            } else {
                // Fallback: store under backup_meta with basename.
                $localName = 'backup_meta/' . basename($pathNorm);
            }

            $bar->setMessage($localName);

            if (is_file($path) && is_readable($path)) {
                // addFile returns false on failure; ignore but keep going.
                if (@$zip->addFile($path, $localName)) {
                    $added++;
                }
            }

            $bar->advance();
        }

        $zip->close();
        $bar->finish();
        $this->newLine(2);

        $this->info('Backup completed. Files added: ' . $added);
        $this->info('ZIP size: ' . $this->humanBytes(@filesize($zipPath) ?: 0));

        $this->cleanupTempDir($tempDir);

        return self::SUCCESS;
    }

    protected function tryCreateDbDump(string $tempDir): ?string
    {
        // Best-effort MySQL/MariaDB dump. If you use sqlite/pgsql, we skip.
        try {
            $connection = config('database.default');
            $driver = (string) config("database.connections.{$connection}.driver", '');
            if ($driver !== 'mysql' && $driver !== 'mariadb') {
                return null;
            }

            $host = (string) config("database.connections.{$connection}.host", '');
            $port = (string) config("database.connections.{$connection}.port", '3306');
            $database = (string) config("database.connections.{$connection}.database", '');
            $username = (string) config("database.connections.{$connection}.username", '');
            $password = (string) config("database.connections.{$connection}.password", '');

            if ($database === '' || $username === '') {
                return null;
            }

            // Ensure DB is reachable (fast).
            DB::connection()->getPdo();

            $dumpPath = rtrim($tempDir, '/') . '/database.sql';

            $mysqldump = trim((string) @shell_exec('command -v mysqldump'));
            if ($mysqldump === '') {
                return null;
            }

            // Use MYSQL_PWD env to avoid leaking password in process list.
            $env = $password !== '' ? 'MYSQL_PWD=' . escapeshellarg($password) . ' ' : '';
            $cmd = $env
                . escapeshellcmd($mysqldump)
                . ' --single-transaction --quick --skip-lock-tables'
                . ' -h ' . escapeshellarg($host)
                . ' -P ' . escapeshellarg($port)
                . ' -u ' . escapeshellarg($username)
                . ' ' . escapeshellarg($database)
                . ' > ' . escapeshellarg($dumpPath) . ' 2>/dev/null';

            $exit = 0;
            @system($cmd, $exit);
            if ($exit !== 0 || !is_file($dumpPath) || filesize($dumpPath) === 0) {
                @unlink($dumpPath);
                return null;
            }

            return $dumpPath;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Create git artifacts that capture "everything we worked on" including uncommitted changes.
     * Returns absolute paths of files to include in the ZIP.
     *
     * @return array<int, string>
     */
    protected function tryCreateGitArtifacts(string $tempDir): array
    {
        $out = [];
        try {
            $root = base_path();
            if (!is_dir($root . '/.git')) {
                return [];
            }

            $git = trim((string) @shell_exec('command -v git'));
            if ($git === '') {
                return [];
            }

            $metaDir = rtrim($tempDir, '/') . '/git';
            @mkdir($metaDir, 0755, true);

            $statusPath = $metaDir . '/status.txt';
            $headPath = $metaDir . '/head.txt';
            $diffPath = $metaDir . '/diff.patch';
            $diffCachedPath = $metaDir . '/diff_cached.patch';
            $bundlePath = $metaDir . '/repo.bundle';

            @file_put_contents($headPath, (string) @shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse --verify HEAD 2>/dev/null'));
            @file_put_contents($statusPath, (string) @shell_exec('git -C ' . escapeshellarg($root) . ' status --porcelain=v1 2>/dev/null'));
            @file_put_contents($diffPath, (string) @shell_exec('git -C ' . escapeshellarg($root) . ' diff 2>/dev/null'));
            @file_put_contents($diffCachedPath, (string) @shell_exec('git -C ' . escapeshellarg($root) . ' diff --cached 2>/dev/null'));

            // Full history bundle (can be restored without GitHub remote)
            // Ignore failure (e.g. if repo is huge or permissions); still keep status/diffs.
            $cmd = 'git -C ' . escapeshellarg($root) . ' bundle create ' . escapeshellarg($bundlePath) . ' --all 2>/dev/null';
            $exit = 0;
            @system($cmd, $exit);
            if ($exit !== 0 || !is_file($bundlePath) || (int) @filesize($bundlePath) <= 0) {
                @unlink($bundlePath);
            }

            foreach ([$headPath, $statusPath, $diffPath, $diffCachedPath, $bundlePath] as $p) {
                if (is_file($p) && (int) @filesize($p) >= 0) {
                    $out[] = $p;
                }
            }
        } catch (\Throwable $e) {
            return $out;
        }

        return $out;
    }

    protected function cleanupTempDir(string $dir): void
    {
        $dir = rtrim($dir, '/');
        if ($dir === '' || !is_dir($dir)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }

        @rmdir($dir);
    }

    protected function humanBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $v = (float) $bytes;
        while ($v >= 1024 && $i < count($units) - 1) {
            $v /= 1024;
            $i++;
        }
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') . ' ' . $units[$i];
    }
}
