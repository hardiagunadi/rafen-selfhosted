<?php

namespace App\Models;

use Database\Factories\WaMultiSessionDeviceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaMultiSessionDevice extends Model
{
    /** @use HasFactory<WaMultiSessionDeviceFactory> */
    use HasFactory;

    protected $fillable = [
        'session_id',
        'wa_number',
        'device_name',
        'is_default',
        'is_active',
        'last_status',
        'last_seen_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
