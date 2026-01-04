<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpAddress extends Model
{
    protected $fillable = [
        'ip',
        'label',
        'notes',
        'first_seen_at',
        'last_seen_at',
        'hit_count',
        'last_path',
        'last_method',
        'last_user_id',
        'last_user_agent',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'hit_count' => 'integer',
    ];
}
