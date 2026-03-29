<?php

namespace App\Models;

use Database\Factories\OltOnuOpticHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OltOnuOpticHistory extends Model
{
    /** @use HasFactory<OltOnuOpticHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'olt_connection_id',
        'olt_onu_optic_id',
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
        'polled_at',
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
            'polled_at' => 'datetime',
        ];
    }

    public function oltConnection(): BelongsTo
    {
        return $this->belongsTo(OltConnection::class);
    }

    public function oltOnuOptic(): BelongsTo
    {
        return $this->belongsTo(OltOnuOptic::class);
    }
}
