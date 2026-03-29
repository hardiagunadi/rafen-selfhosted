<?php

namespace Database\Factories;

use App\Models\CpeDevice;
use App\Models\RadiusAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CpeDevice>
 */
class CpeDeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'radius_account_id' => RadiusAccount::factory(),
            'olt_onu_optic_id' => null,
            'genieacs_device_id' => 'GENIE-'.fake()->unique()->bothify('####??'),
            'param_profile' => fake()->randomElement(['igd', 'device']),
            'serial_number' => strtoupper(fake()->bothify('SN######')),
            'manufacturer' => fake()->randomElement(['ZTE', 'Huawei', 'FiberHome']),
            'model' => fake()->randomElement(['F609', 'HG6245D', 'AN5506']),
            'firmware_version' => fake()->bothify('V#.#.##'),
            'status' => fake()->randomElement(['online', 'offline']),
            'last_seen_at' => now()->subMinutes(fake()->numberBetween(1, 180)),
            'mac_address' => fake()->macAddress(),
            'cached_params' => [
                'profile' => 'igd',
                'wifi_networks' => [],
                'wan_connections' => [],
            ],
        ];
    }
}
