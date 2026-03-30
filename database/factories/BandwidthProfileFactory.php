<?php

namespace Database\Factories;

use App\Models\BandwidthProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BandwidthProfile>
 */
class BandwidthProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'BW '.fake()->unique()->bothify('##M'),
            'upload_min_mbps' => 0,
            'upload_max_mbps' => fake()->numberBetween(5, 200),
            'download_min_mbps' => 0,
            'download_max_mbps' => fake()->numberBetween(10, 300),
        ];
    }
}
