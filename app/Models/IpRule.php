<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpRule extends Model
{
    protected $fillable = [
        'ip',
        'action',
        'enabled',
        'updated_by_user_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function isAllow(): bool
    {
        return $this->enabled && $this->action === 'allow';
    }

    public function isBlock(): bool
    {
        return $this->enabled && $this->action === 'block';
    }
}
