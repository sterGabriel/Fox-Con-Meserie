<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class FullBackup extends Command
{
    protected $signature = 'backup:full {--reason=manual : Backup reason label}';

    protected $description = 'Create a full backup archive (DB dump + project files + storage/app) into storage/app/full-backups';

    public function handle(DatabaseBackupService $dbBackup): int
    {
        $reason = (string) $this->option('reason');
        $ts = date('Ymd_His');

        $backupDir = storage_path('app/full-backups');
        $tmpDir = $backupDir . DIRECTORY_SEPARATOR . 'tmp_' . $ts;
        $archive = $backupDir . DIRECTORY_SEPARATOR . 'iptv-panel_full_' . $ts . '.tar.gz';

        File::ensureDirectoryExists($backupDir);
        File::ensureDirectoryExists($tmpDir);

        $this->info('1) Dumping database...');
        $dbRes = $dbBackup->backup('full_' . ($reason !== '' ? $reason : 'manual'));
        if (($dbRes['ok'] ?? false) !== true) {
            $this->error('DB backup failed: ' . ($dbRes['error'] ?? 'unknown'));
            $this->line('No archive was created.');
            File::deleteDirectory($tmpDir);
            return self::FAILURE;
        }

        $dbDumpPath = (string) ($dbRes['path'] ?? '');
        if ($dbDumpPath === '' || !is_file($dbDumpPath)) {
            $this->error('DB backup returned invalid path.');
            File::deleteDirectory($tmpDir);
            return self::FAILURE;
        }

        $dbDumpBasename = 'database.sql';
        File::copy($dbDumpPath, $tmpDir . DIRECTORY_SEPARATOR . $dbDumpBasename);

        // Minimal manifest to help restore/debug.
        $manifest = [
            'created_at' => date('c'),
            'reason' => $reason,
            'app_env' => (string) config('app.env'),
            'db_dump_source' => $dbDumpPath,
        ];

        // Best-effort git info (only if repo exists)
        try {
            $gitHead = base_path('.git/HEAD');
            if (is_file($gitHead)) {
                $p = new Process(['git', 'rev-parse', 'HEAD'], base_path());
                $p->setTimeout(5);
                $p->run();
                if ($p->isSuccessful()) {
                    $manifest['git_commit'] = trim((string) $p->getOutput());
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        File::put($tmpDir . DIRECTORY_SEPARATOR . 'backup_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('2) Creating full archive (this may take a while)...');

        // Build argv to include .env and our tmp manifest + db.
        $envPath = base_path('.env');
        $args = [
            'tar',
            '-czf',
            $archive,
            '--checkpoint=2000',
            '--checkpoint-action=dot',
            '--warning=no-file-changed',
            '--exclude=vendor',
            '--exclude=node_modules',
            '--exclude=storage/framework',
            '--exclude=storage/app/full-backups',
            '--exclude=storage/app/db-backups',
            '--exclude=.git',
            '-C', base_path(),
            'app',
            'bootstrap',
            'config',
            'database',
            'public',
            'resources',
            'routes',
            'storage/app',
            'storage/logs',
            'artisan',
            'composer.json',
        ];

        if (is_file(base_path('composer.lock'))) $args[] = 'composer.lock';
        if (is_file(base_path('package.json'))) $args[] = 'package.json';
        if (is_file(base_path('package-lock.json'))) $args[] = 'package-lock.json';
        if (is_file(base_path('vite.config.js'))) $args[] = 'vite.config.js';
        if (is_file(base_path('phpunit.xml'))) $args[] = 'phpunit.xml';
        if (is_file(base_path('tailwind.config.js'))) $args[] = 'tailwind.config.js';
        if (is_file(base_path('postcss.config.js'))) $args[] = 'postcss.config.js';
        if (is_file($envPath)) $args[] = '.env';

        // Include our manifest + db dump copy via relative path
        $args[] = '-C';
        $args[] = $backupDir;
        $args[] = 'tmp_' . $ts . '/' . $dbDumpBasename;
        $args[] = 'tmp_' . $ts . '/backup_manifest.json';

        $process = new Process($args);
        $process->setTimeout(0);

        try {
            $wroteAny = false;
            $process->run(function ($type, $buffer) use (&$wroteAny) {
                // tar --checkpoint-action=dot prints '.' periodically (usually to stderr).
                if (!is_string($buffer) || $buffer === '') {
                    return;
                }

                $dots = substr_count($buffer, '.');
                if ($dots > 0) {
                    $this->output->write(str_repeat('.', $dots));
                    $wroteAny = true;
                }
            });

            if ($wroteAny) {
                $this->newLine();
            }

            $exit = (int) $process->getExitCode();
            if ($exit !== 0) {
                $stderr = (string) $process->getErrorOutput();
                $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $stderr))));

                // tar sometimes exits with code 1 when files change during read.
                // If an archive was produced and stderr is either empty or only contains
                // "file changed as we read it" warnings, treat it as success.
                if ($exit === 1) {
                    $archiveSize = is_file($archive) ? @filesize($archive) : false;
                    if (!is_int($archiveSize) || $archiveSize <= 0) {
                        throw new \RuntimeException('tar exited 1 and no valid archive was produced');
                    }

                    $hasNonFileChangedWarnings = false;
                    foreach ($lines as $line) {
                        if (!str_starts_with($line, 'tar: ') || !str_contains($line, 'file changed as we read it')) {
                            $hasNonFileChangedWarnings = true;
                            break;
                        }
                    }

                    if ($hasNonFileChangedWarnings) {
                        throw new \RuntimeException("tar failed (exit {$exit}): " . trim($stderr));
                    }

                    if ($lines !== []) {
                        $this->line('Note: some files changed while archiving; backup may not be perfectly point-in-time consistent.');
                    }
                } else {
                    throw new \RuntimeException("tar failed (exit {$exit}): " . trim($stderr));
                }
            }
        } catch (\Throwable $e) {
            $this->error('Archive failed: ' . $e->getMessage());
            if (is_file($archive)) {
                @unlink($archive);
            }
            File::deleteDirectory($tmpDir);
            return self::FAILURE;
        }

        File::deleteDirectory($tmpDir);

        $size = is_file($archive) ? filesize($archive) : null;
        $this->info('Backup created: ' . $archive);
        if (is_int($size)) {
            $this->line('Size: ' . number_format($size / 1024 / 1024, 2) . ' MB');
        }

        return self::SUCCESS;
    }
}
