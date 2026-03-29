<?php

namespace App\Models;

use Database\Factories\MikrotikConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MikrotikConnection extends Model
{
    /** @use HasFactory<MikrotikConnectionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'api_port',
        'api_ssl_port',
        'use_ssl',
        'username',
        'password',
        'radius_secret',
        'ros_version',
        'api_timeout',
        'notes',
        'is_active',
        'is_online',
        'last_ping_latency_ms',
        'last_ping_at',
        'failed_ping_count',
        'ping_unstable',
        'last_port_open',
        'last_ping_message',
        'auth_port',
        'acct_port',
        'timezone',
        'isolir_url',
        'isolir_setup_done',
        'isolir_pool_name',
        'isolir_pool_range',
        'isolir_gateway',
        'isolir_profile_name',
        'isolir_rate_limit',
        'isolir_setup_at',
        'hotspot_subnet',
    ];

    protected function casts(): array
    {
        return [
            'api_port' => 'integer',
            'api_ssl_port' => 'integer',
            'use_ssl' => 'boolean',
            'api_timeout' => 'integer',
            'is_active' => 'boolean',
            'is_online' => 'boolean',
            'last_ping_latency_ms' => 'integer',
            'last_ping_at' => 'datetime',
            'failed_ping_count' => 'integer',
            'ping_unstable' => 'boolean',
            'last_port_open' => 'boolean',
            'auth_port' => 'integer',
            'acct_port' => 'integer',
            'isolir_setup_done' => 'boolean',
            'isolir_setup_at' => 'datetime',
        ];
    }

    public function radiusAccounts(): HasMany
    {
        return $this->hasMany(RadiusAccount::class);
    }
}
