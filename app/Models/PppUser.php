<?php

namespace App\Models;

use Database\Factories\PppUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PppUser extends Model
{
    /** @use HasFactory<PppUserFactory> */
    use HasFactory;

    protected $fillable = [
        'status_registrasi',
        'tipe_pembayaran',
        'status_bayar',
        'status_akun',
        'ppp_profile_id',
        'tipe_service',
        'tagihkan_ppn',
        'prorata_otomatis',
        'promo_aktif',
        'durasi_promo_bulan',
        'biaya_instalasi',
        'jatuh_tempo',
        'aksi_jatuh_tempo',
        'tipe_ip',
        'profile_group_id',
        'odp_id',
        'ip_static',
        'odp_pop',
        'customer_id',
        'customer_name',
        'nik',
        'nomor_hp',
        'email',
        'alamat',
        'latitude',
        'longitude',
        'location_accuracy_m',
        'location_capture_method',
        'location_captured_at',
        'metode_login',
        'username',
        'ppp_password',
        'password_clientarea',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tagihkan_ppn' => 'boolean',
            'prorata_otomatis' => 'boolean',
            'promo_aktif' => 'boolean',
            'durasi_promo_bulan' => 'integer',
            'biaya_instalasi' => 'decimal:2',
            'jatuh_tempo' => 'date',
            'location_accuracy_m' => 'decimal:2',
            'location_captured_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PppProfile::class, 'ppp_profile_id');
    }

    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function pushSubscriptions(): MorphMany
    {
        return $this->morphMany(PushSubscription::class, 'subscribable');
    }

    public static function generateCustomerId(): string
    {
        $max = static::query()
            ->pluck('customer_id')
            ->filter(fn (mixed $value): bool => is_string($value) && preg_match('/^\d{12}$/', $value) === 1)
            ->map(fn (string $value): int => (int) $value)
            ->max();

        $next = ($max ?? 0) + 1;

        return str_pad((string) $next, 12, '0', STR_PAD_LEFT);
    }
}
