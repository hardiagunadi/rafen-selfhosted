<?php

namespace Database\Factories;

use App\Models\BandwidthProfile;
use App\Models\PppProfile;
use App\Models\ProfileGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PppProfile>
 */
class PppProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'PPP '.fake()->unique()->bothify('##M'),
            'harga_modal' => 100000,
            'harga_promo' => 150000,
            'ppn' => 11,
            'profile_group_id' => ProfileGroup::factory(),
            'bandwidth_profile_id' => BandwidthProfile::factory(),
            'parent_queue' => null,
            'masa_aktif' => 1,
            'satuan' => 'bulan',
        ];
    }
}
