<?php

namespace App\Models;

use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory;

    protected $fillable = [
        'hotspot_profile_id',
        'profile_group_id',
        'batch_name',
        'code',
        'status',
        'username',
        'password',
        'used_at',
        'expired_at',
        'used_by_mac',
        'used_by_ip',
        'mixradius_id',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function hotspotProfile(): BelongsTo
    {
        return $this->belongsTo(HotspotProfile::class);
    }

    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class);
    }

    public function isUnused(): bool
    {
        return $this->status === 'unused';
    }
}
