<?php

namespace Tests\Feature;

use App\Models\LiveChannel;
use App\Models\PlaylistItem;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NowPlayingTest extends TestCase
{
    use RefreshDatabase;

    public function test_now_playing_returns_not_running_when_channel_idle(): void
    {
        $channel = LiveChannel::query()->create([
            'name' => 'Test Channel',
            'slug' => 'test-channel-' . Str::random(8),
            'enabled' => true,
            'status' => 'idle',
        ]);

        $res = $this->actingAs($this->makeUser())
            ->get('/vod-channels/' . $channel->id . '/now-playing');

        $res->assertOk();
        $res->assertJsonPath('status', 'success');
        $res->assertJsonPath('is_running', false);
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
