<?php

namespace App\Models;

use Database\Factories\RadiusAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RadiusAccount extends Model
{
    /** @use HasFactory<RadiusAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'mikrotik_connection_id',
        'username',
        'password',
        'service',
        'ipv4_address',
        'rate_limit',
        'profile',
        'is_active',
        'notes',
        'uptime',
        'caller_id',
        'server_name',
        'bytes_in',
        'bytes_out',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'bytes_in' => 'integer',
            'bytes_out' => 'integer',
        ];
    }

    public function mikrotikConnection(): BelongsTo
    {
        return $this->belongsTo(MikrotikConnection::class);
    }

    public function cpeDevice(): HasOne
    {
        return $this->hasOne(CpeDevice::class);
    }
}
