<?php

namespace App\Models;

use Database\Factories\ProfileGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileGroup extends Model
{
    /** @use HasFactory<ProfileGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'mikrotik_connection_id',
        'type',
        'ip_pool_mode',
        'ip_pool_name',
        'ip_address',
        'netmask',
        'range_start',
        'range_end',
        'dns_servers',
        'parent_queue',
        'host_min',
        'host_max',
    ];

    public function mikrotikConnection(): BelongsTo
    {
        return $this->belongsTo(MikrotikConnection::class);
    }
}
