<?php

namespace Database\Factories;

use App\Models\BandwidthProfile;
use App\Models\HotspotProfile;
use App\Models\ProfileGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HotspotProfile>
 */
class HotspotProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'HS '.fake()->unique()->bothify('##M'),
            'harga_jual' => 5000,
            'harga_promo' => 5000,
            'ppn' => 11,
            'bandwidth_profile_id' => BandwidthProfile::factory(),
            'profile_type' => 'unlimited',
            'limit_type' => null,
            'time_limit_value' => null,
            'time_limit_unit' => null,
            'quota_limit_value' => null,
            'quota_limit_unit' => null,
            'masa_aktif_value' => 1,
            'masa_aktif_unit' => 'hari',
            'profile_group_id' => ProfileGroup::factory(),
            'parent_queue' => null,
            'shared_users' => 1,
            'prioritas' => 'default',
        ];
    }
}
