<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaylistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'vod_channel_id',
        'video_id',
        'sort_order',
        'start_at',
        'end_at',
    ];

    public function channel()
    {
        return $this->belongsTo(LiveChannel::class, 'vod_channel_id');
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
