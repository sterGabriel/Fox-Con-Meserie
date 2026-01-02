<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class GitAutoBackup extends Command
{
    protected $signature = 'git:autobackup
        {--remote=origin : Git remote name}
        {--push-branch=backup/auto : Remote branch name to push HEAD to}
        {--no-push : Commit only, do not push}
        {--dry-run : Show what would be committed/pushed without changing anything}';

    protected $description = 'Auto-commit and push a safe subset of working tree changes to GitHub for backups.';

    public function handle(): int
    {
        $repoRoot = base_path();
        $remote = (string) ($this->option('remote') ?: 'origin');
        $pushBranch = (string) ($this->option('push-branch') ?: 'backup/auto');
        $noPush = (bool) $this->option('no-push');
        $dryRun = (bool) $this->option('dry-run');

        if (!is_dir($repoRoot . '/.git')) {
            $this->error('Not a git repository: ' . $repoRoot);
            return self::FAILURE;
        }

        // Avoid committing huge/runtime directories or secrets.
        $excludePrefixes = [
            'storage/app/streams/',
            'storage/app/full-backups/',
            'storage/app/db-backups/',
            'storage/app/backups/',
            'storage/logs/',
            'storage/framework/',
            'public/storage/',
            'public/build/',
            'vendor/',
            'node_modules/',
        ];

        $this->line('Scanning git status…');
        [$statusCode, $statusOut] = $this->runGit($repoRoot, ['status', '--porcelain=v1', '-z']);
        if ($statusCode !== 0) {
            $this->error('git status failed');
            $this->line($statusOut);
            return self::FAILURE;
        }

        $paths = $this->parsePorcelainPaths($statusOut);
        $paths = array_values(array_unique(array_filter($paths, function (string $path) use ($excludePrefixes) {
            $pathNorm = str_replace('\\', '/', $path);

            // Do not ever auto-commit env files.
            $base = basename($pathNorm);
            if ($base === '.env' || str_starts_with($base, '.env.')) {
                return false;
            }

            foreach ($excludePrefixes as $prefix) {
                if (str_starts_with($pathNorm, $prefix)) {
                    return false;
                }
            }

            return true;
        })));

        if (empty($paths)) {
            $this->info('No eligible changes to commit.');
            return self::SUCCESS;
        }

        $this->line('Eligible changes: ' . count($paths));

        if ($dryRun) {
            foreach ($paths as $p) {
                $this->line(' - ' . $p);
            }
            $this->line('Dry-run: would stage, commit, and ' . ($noPush ? 'NOT push.' : ('push to ' . $remote . ' ' . $pushBranch . '.')));
            return self::SUCCESS;
        }

        // Stage changes path-by-path (safe filtering).
        $this->line('Staging changes…');
        foreach ($paths as $p) {
            // Use -A to include deletions for that path.
            [$code] = $this->runGit($repoRoot, ['add', '-A', '--', $p]);
            if ($code !== 0) {
                $this->warn('Could not stage: ' . $p);
            }
        }

        // If nothing staged, exit.
        [$diffCode] = $this->runGit($repoRoot, ['diff', '--cached', '--quiet']);
        if ($diffCode === 0) {
            $this->info('Nothing staged after filtering; skipping commit.');
            return self::SUCCESS;
        }

        $ts = date('Y-m-d H:i:s');
        $host = trim((string) @gethostname());
        $msg = "Auto backup: {$ts}" . ($host !== '' ? " ({$host})" : '');

        $this->line('Committing…');
        [$commitCode, $commitOut] = $this->runGit($repoRoot, ['commit', '-m', $msg]);
        $this->line(trim($commitOut));
        if ($commitCode !== 0) {
            $this->error('git commit failed');
            return self::FAILURE;
        }

        if ($noPush) {
            $this->info('Commit done. Push skipped (--no-push).');
            return self::SUCCESS;
        }

        $this->line("Pushing HEAD to {$remote}:{$pushBranch}…");
        [$pushCode, $pushOut] = $this->runGit($repoRoot, ['push', $remote, 'HEAD:refs/heads/' . $pushBranch]);
        $this->line(trim($pushOut));
        if ($pushCode !== 0) {
            $this->error('git push failed');
            return self::FAILURE;
        }

        $this->info('GitHub backup push completed.');
        return self::SUCCESS;
    }

    /**
     * @return array{0:int,1:string}
     */
    protected function runGit(string $cwd, array $args): array
    {
        $cmd = array_merge(['git', '-C', $cwd], $args);
        $p = new Process($cmd, $cwd, null, null, 300);
        $p->run();
        $out = (string) $p->getOutput();
        $err = (string) $p->getErrorOutput();
        $combined = trim($out . (strlen($err) ? "\n" . $err : ''));
        return [$p->getExitCode() ?? 1, $combined];
    }

    /**
     * Parse `git status --porcelain=v1 -z` output into paths.
     *
     * @return array<int, string>
     */
    protected function parsePorcelainPaths(string $zOut): array
    {
        if ($zOut === '') return [];

        $tokens = explode("\0", $zOut);
        $paths = [];

        for ($i = 0; $i < count($tokens); $i++) {
            $t = $tokens[$i];
            if ($t === '') continue;

            // Format: XY<space>path OR XY<space>old_path (then next token is new_path) for renames/copies.
            if (strlen($t) < 4) continue;
            $xy = substr($t, 0, 2);
            $path = substr($t, 3);

            if ($xy !== '' && ($xy[0] === 'R' || $xy[0] === 'C' || $xy[1] === 'R' || $xy[1] === 'C')) {
                // Next token is the new path.
                if (($i + 1) < count($tokens) && $tokens[$i + 1] !== '') {
                    $i++;
                    $path = $tokens[$i];
                }
            }

            $path = trim($path);
            if ($path !== '') {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}
