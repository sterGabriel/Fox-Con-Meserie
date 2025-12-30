<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title',
        'file_path',
        'video_category_id', // dacă e null, e ok

        // metadata
        'duration_seconds',
        'bitrate_kbps',
        'resolution',
        'size_bytes',
        'format',

        // TMDB
        'tmdb_id',
        'tmdb_type',
        'tmdb_poster_path',
        'tmdb_backdrop_path',
        'tmdb_genres',
    ];

    public function playlistItems()
    {
        return $this->hasMany(PlaylistItem::class);
    }

    public function getFilePathAttribute($value)
    {
        return is_string($value) ? trim($value) : $value;
    }

    public function setFilePathAttribute($value): void
    {
        $this->attributes['file_path'] = is_string($value) ? trim($value) : $value;
    }

    public function category()
    {
        return $this->belongsTo(VideoCategory::class, 'video_category_id');
    }

    public function encodingJobs()
    {
        return $this->hasMany(EncodingJob::class);
    }
}
