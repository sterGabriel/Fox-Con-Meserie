<?php

namespace App\Jobs;

use App\Models\AppSetting;
use App\Models\Video;
use App\Services\TmdbSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TmdbSyncVideosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array<int> */
    public array $videoIds;

    /**
     * @param array<int> $videoIds
     */
    public function __construct(array $videoIds)
    {
        $this->videoIds = array_values(array_unique(array_map('intval', $videoIds)));
        $this->onQueue('default');
    }

    public function handle(TmdbSyncService $sync): void
    {
        $apiKey = (string) AppSetting::getValue('tmdb_api_key', (string) env('TMDB_API_KEY', ''));
        if (trim($apiKey) === '') {
            return;
        }

        $videos = Video::query()
            ->whereIn('id', $this->videoIds)
            ->get(['id', 'title', 'file_path', 'tmdb_id', 'tmdb_type', 'tmdb_poster_path', 'tmdb_backdrop_path', 'tmdb_genres']);

        foreach ($videos as $video) {
            try {
                $sync->syncVideo($video, $apiKey);
            } catch (\Throwable $e) {
                // swallow per-video failures
                continue;
            }
        }
    }
}
