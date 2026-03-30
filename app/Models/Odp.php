<?php

namespace App\Models;

use Database\Factories\OdpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odp extends Model
{
    /** @use HasFactory<OdpFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'area',
        'latitude',
        'longitude',
        'capacity_ports',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'capacity_ports' => 'integer',
        ];
    }

    public function pppUsers(): HasMany
    {
        return $this->hasMany(PppUser::class, 'odp_id');
    }
}
