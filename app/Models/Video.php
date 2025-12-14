<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title',
        'file_path',
        'video_category_id', // dacă e null, e ok
    ];

    public function playlistItems()
    {
        return $this->hasMany(PlaylistItem::class);
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
