<?php

namespace Tests\Feature;

use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EpgAllTest extends TestCase
{
    use RefreshDatabase;

    public function test_epg_all_xml_returns_xmltv_with_channel_and_programme(): void
    {
        $channel = LiveChannel::query()->create([
            'name' => 'Test Channel',
            'slug' => 'test-channel-' . Str::random(8),
            'enabled' => true,
            'status' => 'idle',
        ]);

        $video = Video::query()->create([
            'title' => 'Test Movie',
            'file_path' => '/tmp/test.mp4',
            'duration_seconds' => 120,
        ]);

        PlaylistItem::query()->create([
            'live_channel_id' => $channel->id,
            'vod_channel_id' => $channel->id,
            'video_id' => $video->id,
            'sort_order' => 1,
        ]);

        $res = $this->get('/epg/all.xml');

        $res->assertOk();
        $res->assertHeader('Content-Type', 'application/xml; charset=utf-8');
        $res->assertSee('<tv', false);
        $res->assertSee('vod-channel-' . $channel->id, false);
        $res->assertSee('<programme', false);
        $res->assertSee('Test Movie', false);
    }
}
