<?php

namespace App\Models;

use Database\Factories\RadiusNasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadiusNas extends Model
{
    /** @use HasFactory<RadiusNasFactory> */
    use HasFactory;

    protected $table = 'radius_nas';

    protected $fillable = [
        'name',
        'shortname',
        'ip_address',
        'secret',
        'require_message_authenticator',
        'auth_port',
        'acct_port',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'require_message_authenticator' => 'boolean',
            'auth_port' => 'integer',
            'acct_port' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
