<?php

namespace App\Models;

use Database\Factories\OltOnuOpticFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OltOnuOptic extends Model
{
    /** @use HasFactory<OltOnuOpticFactory> */
    use HasFactory;

    protected $fillable = [
        'olt_connection_id',
        'onu_index',
        'pon_interface',
        'onu_number',
        'serial_number',
        'onu_name',
        'distance_m',
        'rx_onu_dbm',
        'tx_onu_dbm',
        'rx_olt_dbm',
        'tx_olt_dbm',
        'status',
        'raw_payload',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'distance_m' => 'integer',
            'rx_onu_dbm' => 'decimal:2',
            'tx_onu_dbm' => 'decimal:2',
            'rx_olt_dbm' => 'decimal:2',
            'tx_olt_dbm' => 'decimal:2',
            'raw_payload' => 'array',
            'last_seen_at' => 'datetime',
        ];
    }

    public function oltConnection(): BelongsTo
    {
        return $this->belongsTo(OltConnection::class);
    }
}
