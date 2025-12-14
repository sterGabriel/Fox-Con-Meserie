<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncodingJob extends Model
{
    protected $fillable = [
        'live_channel_id',
        'video_id',
        'status',
        'progress',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function channel()
    {
        return $this->belongsTo(LiveChannel::class, 'live_channel_id');
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
