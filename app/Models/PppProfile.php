<?php

namespace App\Models;

use Database\Factories\PppProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PppProfile extends Model
{
    /** @use HasFactory<PppProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'harga_modal',
        'harga_promo',
        'ppn',
        'profile_group_id',
        'bandwidth_profile_id',
        'parent_queue',
        'masa_aktif',
        'satuan',
    ];

    protected function casts(): array
    {
        return [
            'harga_modal' => 'decimal:2',
            'harga_promo' => 'decimal:2',
            'ppn' => 'decimal:2',
            'masa_aktif' => 'integer',
        ];
    }

    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class);
    }

    public function bandwidthProfile(): BelongsTo
    {
        return $this->belongsTo(BandwidthProfile::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'ppp_profile_id');
    }
}
