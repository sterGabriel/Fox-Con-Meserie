<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PurgeBootstrappedLibrary extends Command
{
    protected $signature = 'media:purge-bootstrapped
        {--root= : Root folder that was bootstrapped (e.g. /media/videos)}
        {--force : Actually delete records}';

    protected $description = 'Remove video categories + videos that were bootstrapped from server folders (keeps files on disk).';

    public function handle(): int
    {
        $root = (string) ($this->option('root') ?: config('app.media_root', env('MEDIA_ROOT')));
        $root = rtrim($root, '/');

        if ($root === '') {
            $this->error('Root is empty. Use --root=/media/videos');
            return 1;
        }

        $force = (bool) $this->option('force');

        // Heuristic: categories that look like folder-derived entries.
        // - description null
        // - source_path set
        // - source_path under root
        $categories = VideoCategory::query()
            ->whereNull('description')
            ->whereNotNull('source_path')
            ->where('source_path', 'like', $root . '%')
            ->orderBy('id')
            ->get();

        if ($categories->isEmpty()) {
            $this->info("No bootstrapped categories found under: {$root}");
            return 0;
        }

        $categoryIds = $categories->pluck('id')->all();

        $videosQuery = Video::query()->whereIn('video_category_id', $categoryIds);
        $videosCount = (clone $videosQuery)->count();

        $this->line('Bootstrapped categories detected: ' . $categories->count());
        foreach ($categories as $cat) {
            $this->line("- [{$cat->id}] {$cat->name} | {$cat->source_path}");
        }
        $this->line('Videos in those categories: ' . $videosCount);

        if (!$force) {
            $this->newLine();
            $this->warn('Dry run only. To delete, re-run with:');
            $this->line('  php artisan media:purge-bootstrapped --root=' . escapeshellarg($root) . ' --force');
            return 0;
        }

        $deletedVideos = $videosQuery->delete();

        // Remove categories only after videos are deleted.
        $deletedCategories = VideoCategory::query()->whereIn('id', $categoryIds)->delete();

        $this->info("Deleted videos: {$deletedVideos}");
        $this->info("Deleted categories: {$deletedCategories}");
        $this->info('Files on disk were NOT touched.');

        return 0;
    }
}
