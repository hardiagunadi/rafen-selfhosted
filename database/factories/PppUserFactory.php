<?php

namespace Database\Factories;

use App\Models\PppProfile;
use App\Models\PppUser;
use App\Models\ProfileGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PppUser>
 */
class PppUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status_registrasi' => 'aktif',
            'tipe_pembayaran' => 'prepaid',
            'status_bayar' => 'belum_bayar',
            'status_akun' => 'enable',
            'ppp_profile_id' => PppProfile::factory(),
            'tipe_service' => 'pppoe',
            'tagihkan_ppn' => true,
            'prorata_otomatis' => false,
            'promo_aktif' => false,
            'durasi_promo_bulan' => 0,
            'biaya_instalasi' => 0,
            'jatuh_tempo' => now()->addMonth()->toDateString(),
            'aksi_jatuh_tempo' => 'isolir',
            'tipe_ip' => 'dhcp',
            'profile_group_id' => ProfileGroup::factory(),
            'odp_id' => null,
            'ip_static' => null,
            'odp_pop' => null,
            'customer_id' => fake()->unique()->numerify('############'),
            'customer_name' => fake()->name(),
            'nik' => fake()->numerify('################'),
            'nomor_hp' => fake()->unique()->numerify('62812########'),
            'email' => fake()->unique()->safeEmail(),
            'alamat' => fake()->address(),
            'latitude' => null,
            'longitude' => null,
            'location_accuracy_m' => null,
            'location_capture_method' => null,
            'location_captured_at' => null,
            'metode_login' => 'username_password',
            'username' => fake()->unique()->userName(),
            'ppp_password' => 'secret123',
            'password_clientarea' => 'secret123',
            'catatan' => null,
        ];
    }
}
