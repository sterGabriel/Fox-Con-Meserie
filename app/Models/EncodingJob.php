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
        'settings',
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
        'settings' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getOutputPathAttribute($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        // Backwards compatibility: existing rows may store an absolute path.
        if (str_starts_with($value, '/')) {
            return $value;
        }

        // New format: relative to storage/app
        return storage_path('app/' . ltrim($value, '/'));
    }

    public function setOutputPathAttribute($value): void
    {
        if (!is_string($value) || trim($value) === '') {
            $this->attributes['output_path'] = $value;
            return;
        }

        $normalized = str_replace('\\', '/', trim($value));
        $appBase = str_replace('\\', '/', rtrim(storage_path('app'), '/')) . '/';

        // If an absolute storage/app path was provided, store it as relative.
        if (str_starts_with($normalized, $appBase)) {
            $this->attributes['output_path'] = ltrim(substr($normalized, strlen($appBase)), '/');
            return;
        }

        $this->attributes['output_path'] = trim($value);
    }

    public function getLogPathAttribute($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        // Backwards compatibility: existing rows may store an absolute path.
        if (str_starts_with($value, '/')) {
            return $value;
        }

        // New format: relative to storage/
        return storage_path(ltrim($value, '/'));
    }

    public function setLogPathAttribute($value): void
    {
        if (!is_string($value) || trim($value) === '') {
            $this->attributes['log_path'] = $value;
            return;
        }

        $normalized = str_replace('\\', '/', trim($value));
        $storageBase = str_replace('\\', '/', rtrim(storage_path(), '/')) . '/';

        // If an absolute storage path was provided, store it as relative.
        if (str_starts_with($normalized, $storageBase)) {
            $this->attributes['log_path'] = ltrim(substr($normalized, strlen($storageBase)), '/');
            return;
        }

        $this->attributes['log_path'] = trim($value);
    }

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
