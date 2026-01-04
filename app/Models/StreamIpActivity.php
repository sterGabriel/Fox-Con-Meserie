<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StreamIpActivity extends Model
{
    protected $fillable = [
        'channel_id',
        'ip',
        'first_seen_at',
        'last_seen_at',
        'hit_count',
        'last_file',
        'last_method',
        'last_user_agent',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'hit_count' => 'integer',
        'channel_id' => 'integer',
    ];
}
