<?php

namespace Tests\Feature;

use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaylistPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_playlist_page_renders_without_leaking_template_directives(): void
    {
        $channel = LiveChannel::query()->create([
            'name' => 'Test Channel',
            'slug' => 'test-channel-' . Str::random(8),
            'enabled' => true,
            'status' => 'idle',
        ]);

        $video = Video::query()->create([
            'title' => '1234567',
            'file_path' => '/tmp/Drama.Movie.2024.mkv',
            'duration_seconds' => 120,
        ]);

        PlaylistItem::query()->create([
            'live_channel_id' => $channel->id,
            'vod_channel_id' => $channel->id,
            'video_id' => $video->id,
            'sort_order' => 1,
        ]);

        $res = $this->actingAs($this->makeUser())
            ->get('/vod-channels/' . $channel->id . '/playlist');

        $res->assertOk();
        $res->assertDontSee('@php', false);
        $res->assertSee('Stream URLs', false);
    }

    private function makeUser()
    {
        return \App\Models\User::query()->create([
            'name' => 'Test',
            'email' => 't' . Str::random(6) . '@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}
