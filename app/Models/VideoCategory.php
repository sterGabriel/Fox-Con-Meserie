<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'source_path',
        // orice alte coloane mai ai (slug, tmdb_id etc.)
    ];

    public function channels()
    {
        return $this->hasMany(LiveChannel::class, 'video_category', 'id');
    }

    public function videos()
    {
        return $this->hasMany(Video::class, 'video_category_id');
    }
}
