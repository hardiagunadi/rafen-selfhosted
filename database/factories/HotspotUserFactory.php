<?php

namespace Database\Factories;

use App\Models\HotspotProfile;
use App\Models\HotspotUser;
use App\Models\ProfileGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HotspotUser>
 */
class HotspotUserFactory extends Factory
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
            'hotspot_profile_id' => HotspotProfile::factory(),
            'profile_group_id' => ProfileGroup::factory(),
            'tagihkan_ppn' => false,
            'biaya_instalasi' => 0,
            'jatuh_tempo' => now()->addDay()->toDateString(),
            'aksi_jatuh_tempo' => 'isolir',
            'customer_id' => fake()->unique()->numerify('MX-######'),
            'customer_name' => fake()->name(),
            'nik' => fake()->numerify('################'),
            'nomor_hp' => fake()->numerify('62812########'),
            'email' => fake()->safeEmail(),
            'alamat' => fake()->address(),
            'username' => fake()->unique()->userName(),
            'metode_login' => 'username_password',
            'hotspot_password' => 'secret123',
            'catatan' => null,
            'mixradius_id' => null,
        ];
    }
}
