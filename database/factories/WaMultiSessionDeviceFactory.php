<?php

namespace Database\Factories;

use App\Models\WaMultiSessionDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaMultiSessionDevice>
 */
class WaMultiSessionDeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => 'session-'.fake()->unique()->bothify('####'),
            'wa_number' => '62812'.fake()->numerify('######'),
            'device_name' => 'Device '.fake()->unique()->bothify('##'),
            'is_default' => false,
            'is_active' => true,
            'last_status' => 'connected',
            'last_seen_at' => now(),
            'meta' => [],
        ];
    }
}
