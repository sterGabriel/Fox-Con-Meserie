<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BootstrapMediaLibrary extends Command
{
    protected $signature = 'media:bootstrap-library
        {--root= : Root media folder (defaults to MEDIA_ROOT)}
        {--depth=1 : Folder depth to convert into categories (1=top-level, 2=top-level + one subfolder)}
        {--import : Actually create Video records (otherwise only prints plan)}';

    protected $description = 'Create video categories from folders and optionally import videos from disk into the DB.';

    private array $allowedExtensions = ['mp4', 'mkv', 'avi', 'mov', 'webm', 'flv', 'wmv', 'ts'];

    public function handle(): int
    {
        $root = (string) ($this->option('root') ?: config('app.media_root', env('MEDIA_ROOT')));
        $root = rtrim($root, '/');
        $depth = max(1, min(2, (int) $this->option('depth')));
        $doImport = (bool) $this->option('import');

        if ($root === '' || !is_dir($root) || !is_readable($root)) {
            $this->error("MEDIA root not found/readable: {$root}");
            return 1;
        }

        $this->info("Using media root: {$root}");
        $this->info("Category depth: {$depth}");
        $this->info($doImport ? 'Mode: IMPORT' : 'Mode: PLAN ONLY (use --import)');

        $folders = $this->collectCategoryFolders($root, $depth);
        if (empty($folders)) {
            $this->warn('No folders found to create categories from.');
            return 0;
        }

        $createdCategories = 0;
        $updatedCategories = 0;
        $importedVideos = 0;
        $skippedVideos = 0;

        foreach ($folders as $folder) {
            $name = $folder['name'];
            $path = $folder['path'];

            [$category, $wasCreated] = $this->upsertCategory($name, $path);
            $createdCategories += $wasCreated ? 1 : 0;
            $updatedCategories += $wasCreated ? 0 : 1;

            $this->line("- Category: {$category->name} | {$category->source_path}");

            if (!$doImport) {
                continue;
            }

            [$imp, $skip] = $this->importVideosForCategory($category);
            $importedVideos += $imp;
            $skippedVideos += $skip;
        }

        $this->newLine();
        $this->info("Categories: created={$createdCategories}, updated={$updatedCategories}");
        if ($doImport) {
            $this->info("Videos: imported={$importedVideos}, skipped_existing={$skippedVideos}");
            $this->info("Next: run 'php artisan videos:sync-metadata' to fill duration/resolution/size.");
        }

        return 0;
    }

    private function collectCategoryFolders(string $root, int $depth): array
    {
        $folders = [];

        $top = @scandir($root) ?: [];
        foreach ($top as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $full = $root . '/' . $entry;
            if (!is_dir($full) || !is_readable($full)) continue;

            if ($depth === 1) {
                $folders[] = [
                    'name' => $entry,
                    'path' => $full,
                ];
                continue;
            }

            // depth=2: create categories for subfolders when present, else for top folder
            $subs = @scandir($full) ?: [];
            $subFolders = [];
            foreach ($subs as $sub) {
                if ($sub === '.' || $sub === '..') continue;
                $subFull = $full . '/' . $sub;
                if (is_dir($subFull) && is_readable($subFull)) {
                    $subFolders[] = ['name' => $entry . ' / ' . $sub, 'path' => $subFull];
                }
            }

            if (!empty($subFolders)) {
                foreach ($subFolders as $sf) $folders[] = $sf;
            } else {
                $folders[] = ['name' => $entry, 'path' => $full];
            }
        }

        // Stable ordering
        usort($folders, fn ($a, $b) => strcmp($a['name'], $b['name']));
        return $folders;
    }

    private function upsertCategory(string $name, string $sourcePath): array
    {
        $name = trim($name);
        $sourcePath = rtrim($sourcePath, '/');

        $existing = VideoCategory::query()->where('name', $name)->first();
        if ($existing) {
            $existing->update(['source_path' => $sourcePath]);
            return [$existing, false];
        }

        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : ('cat-' . Str::random(8));
        $i = 2;
        while (VideoCategory::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }

        $cat = VideoCategory::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'source_path' => $sourcePath,
        ]);

        return [$cat, true];
    }

    private function importVideosForCategory(VideoCategory $category): array
    {
        $path = (string) ($category->source_path ?? '');
        if ($path === '' || !is_dir($path) || !is_readable($path)) {
            return [0, 0];
        }

        $imported = 0;
        $skipped = 0;

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $fileInfo) {
            if (!$fileInfo->isFile()) continue;
            $ext = strtolower($fileInfo->getExtension());
            if (!in_array($ext, $this->allowedExtensions, true)) continue;

            $fullPath = $fileInfo->getRealPath();
            if (!$fullPath || !is_readable($fullPath)) continue;

            if (Video::query()->where('file_path', $fullPath)->exists()) {
                $skipped++;
                continue;
            }

            Video::query()->create([
                'title' => pathinfo($fullPath, PATHINFO_FILENAME),
                'file_path' => $fullPath,
                'video_category_id' => $category->id,
                'format' => $ext,
                'size_bytes' => @filesize($fullPath) ?: null,
            ]);

            $imported++;
            if ($imported % 100 === 0) {
                $this->line("  imported {$imported}...");
            }
        }

        return [$imported, $skipped];
    }
}
