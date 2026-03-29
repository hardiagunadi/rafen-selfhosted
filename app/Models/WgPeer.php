<?php

namespace App\Models;

use Database\Factories\WgPeerFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WgPeer extends Model
{
    /** @use HasFactory<WgPeerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'public_key',
        'private_key',
        'preshared_key',
        'vpn_ip',
        'extra_allowed_ips',
        'is_active',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    protected function endpointHost(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) (config('wg.host') ?: parse_url((string) config('app.url'), PHP_URL_HOST) ?: ''),
        );
    }
}
