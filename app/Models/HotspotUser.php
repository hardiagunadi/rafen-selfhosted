<?php

namespace App\Models;

use Database\Factories\HotspotUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotspotUser extends Model
{
    /** @use HasFactory<HotspotUserFactory> */
    use HasFactory;

    protected $fillable = [
        'status_registrasi',
        'tipe_pembayaran',
        'status_bayar',
        'status_akun',
        'hotspot_profile_id',
        'profile_group_id',
        'tagihkan_ppn',
        'biaya_instalasi',
        'jatuh_tempo',
        'aksi_jatuh_tempo',
        'customer_id',
        'customer_name',
        'nik',
        'nomor_hp',
        'email',
        'alamat',
        'username',
        'metode_login',
        'hotspot_password',
        'catatan',
        'mixradius_id',
    ];

    protected function casts(): array
    {
        return [
            'tagihkan_ppn' => 'boolean',
            'biaya_instalasi' => 'decimal:2',
            'jatuh_tempo' => 'date',
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

    public static function generateCustomerId(): string
    {
        $max = static::query()
            ->pluck('customer_id')
            ->filter(fn (mixed $value): bool => is_string($value) && preg_match('/^MX-\d{6}$/', $value) === 1)
            ->map(fn (string $value): int => (int) substr($value, 3))
            ->max();

        $next = ($max ?? 0) + 1;

        return 'MX-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
