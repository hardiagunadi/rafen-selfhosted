<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\HotspotProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotspotProfile extends Model
{
    /** @use HasFactory<HotspotProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'harga_jual',
        'harga_promo',
        'ppn',
        'bandwidth_profile_id',
        'profile_type',
        'limit_type',
        'time_limit_value',
        'time_limit_unit',
        'quota_limit_value',
        'quota_limit_unit',
        'masa_aktif_value',
        'masa_aktif_unit',
        'profile_group_id',
        'parent_queue',
        'shared_users',
        'prioritas',
    ];

    protected function casts(): array
    {
        return [
            'harga_jual' => 'decimal:2',
            'harga_promo' => 'decimal:2',
            'ppn' => 'decimal:2',
            'time_limit_value' => 'integer',
            'quota_limit_value' => 'decimal:2',
            'masa_aktif_value' => 'integer',
            'shared_users' => 'integer',
        ];
    }

    public function hotspotUsers(): HasMany
    {
        return $this->hasMany(HotspotUser::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function bandwidthProfile(): BelongsTo
    {
        return $this->belongsTo(BandwidthProfile::class);
    }

    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class);
    }

    public function computeExpiredAt(Carbon $from): ?Carbon
    {
        if (! $this->masa_aktif_value || ! $this->masa_aktif_unit) {
            return null;
        }

        return match ($this->masa_aktif_unit) {
            'menit' => $from->copy()->addMinutes($this->masa_aktif_value),
            'jam' => $from->copy()->addHours($this->masa_aktif_value),
            'hari' => $from->copy()->addDays($this->masa_aktif_value),
            'bulan' => $from->copy()->addMonths($this->masa_aktif_value),
            default => null,
        };
    }
}
