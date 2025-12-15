<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncodingJob extends Model
{
    protected $fillable = [
        'live_channel_id',
        'channel_id',
        'video_id',
        'playlist_item_id',
        'input_path',
        'output_path',
        'status',
        'progress',
        'started_at',
        'finished_at',
        'completed_at',
        'error_message',
        'ffmpeg_command',
        'pid',
        'log_path',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function channel()
    {
        return $this->belongsTo(LiveChannel::class, 'live_channel_id');
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function playlistItem()
    {
        return $this->belongsTo(PlaylistItem::class);
    }
}
