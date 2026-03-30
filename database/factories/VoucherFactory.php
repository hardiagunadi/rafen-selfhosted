<?php

namespace Database\Factories;

use App\Models\HotspotProfile;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->bothify('??##??##'));

        return [
            'hotspot_profile_id' => HotspotProfile::factory(),
            'profile_group_id' => null,
            'batch_name' => 'VC-'.now()->format('Ymd'),
            'code' => $code,
            'status' => 'unused',
            'username' => $code,
            'password' => $code,
            'used_at' => null,
            'expired_at' => null,
            'used_by_mac' => null,
            'used_by_ip' => null,
            'mixradius_id' => null,
        ];
    }
}
