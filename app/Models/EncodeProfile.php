<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncodeProfile extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'width',
        'height',
        'fps_mode',
        'fps',
        'video_bitrate_k',
        'maxrate_k',
        'bufsize_k',
        'crf',
        'preset',
        'profile',
        'pix_fmt',
        'gop',
        'audio_codec',
        'audio_bitrate_k',
        'audio_channels',
        'container',
        'extra_ffmpeg',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function channels()
    {
        return $this->hasMany(LiveChannel::class, 'encode_profile_id');
    }
}
