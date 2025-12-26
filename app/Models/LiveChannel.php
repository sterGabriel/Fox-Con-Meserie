<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveChannel extends Model
{
    use HasFactory;

    // Lăsăm toate coloanele mass-assignable, ca să nu ne batem capul
    protected $guarded = [];

    // Relație cu playlist items (dacă o folosești)
    public function playlistItems()
    {
        // Canonical FK in DB is live_channel_id; keep code aligned with schema.
        return $this->hasMany(PlaylistItem::class, 'live_channel_id');
    }

    // Relație cu video category
    public function videoCategory()
    {
        return $this->belongsTo(VideoCategory::class, 'video_category_id');
    }

    // Relație cu encoding jobs (coada de encodare)
    public function encodingJobs()
    {
        return $this->hasMany(EncodingJob::class);
    }

    // Relație cu encode profile
    public function encodeProfile()
    {
        return $this->belongsTo(EncodeProfile::class, 'encode_profile_id');
    }

    /**
     * Helper: setări de overlay (logo, titlu, timer)
     * – vom folosi asta în EncodingJob când construim comanda ffmpeg
     */
    public function overlayConfig(): array
    {
        return [
            'logo_path'        => $this->logo_path,
            'logo_width'       => $this->logo_width,
            'logo_height'      => $this->logo_height,
            'logo_pos_x'       => $this->logo_position_x,
            'logo_pos_y'       => $this->logo_position_y,

            'title_font_size'  => $this->title_font_size,
            'title_pos_x'      => $this->title_position_x,
            'title_pos_y'      => $this->title_position_y,

            'timer_font_size'  => $this->timer_font_size,
            'timer_pos_x'      => $this->timer_position_x,
            'timer_pos_y'      => $this->timer_position_y,

            // fallback-uri simple, în caz că sunt NULL
            'resolution'       => $this->resolution ?? '1280x720',
            'video_bitrate'    => $this->video_bitrate ?? 4500,
            'audio_bitrate'    => $this->audio_bitrate ?? 128,

            'fps'              => $this->fps ?? 25,
            'audio_codec'      => $this->audio_codec ?? 'aac',
        ];
    }
}
