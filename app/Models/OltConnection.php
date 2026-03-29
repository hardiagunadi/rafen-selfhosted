<?php

namespace App\Models;

use Database\Factories\OltConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OltConnection extends Model
{
    /** @use HasFactory<OltConnectionFactory> */
    use HasFactory;

    protected $fillable = [
        'vendor',
        'name',
        'olt_model',
        'host',
        'snmp_port',
        'snmp_version',
        'snmp_community',
        'snmp_write_community',
        'snmp_timeout',
        'snmp_retries',
        'is_active',
        'oid_serial',
        'oid_onu_name',
        'oid_rx_onu',
        'oid_tx_onu',
        'oid_rx_olt',
        'oid_tx_olt',
        'oid_distance',
        'oid_status',
        'oid_reboot_onu',
        'last_polled_at',
        'last_poll_success',
        'last_poll_message',
    ];

    protected function casts(): array
    {
        return [
            'snmp_port' => 'integer',
            'snmp_timeout' => 'integer',
            'snmp_retries' => 'integer',
            'is_active' => 'boolean',
            'last_polled_at' => 'datetime',
            'last_poll_success' => 'boolean',
        ];
    }

    public function onuOptics(): HasMany
    {
        return $this->hasMany(OltOnuOptic::class);
    }

    public function onuOpticHistories(): HasMany
    {
        return $this->hasMany(OltOnuOpticHistory::class);
    }
}
