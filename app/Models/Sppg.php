<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sppg extends Model
{
    protected $fillable = [
        'code',
        'name',
        'api_key',
        'hmac_secret',
        'is_active',
        'rate_limit_per_minute',
        'timezone',
        'webhook_url',
        'pull_base_url',
        'ip_whitelist'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ip_whitelist' => 'array',
    ];

    public function intakes(): HasMany
    {
        return $this->hasMany(SppgIntake::class);
    }
}
