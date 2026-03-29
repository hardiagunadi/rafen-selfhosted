<?php

namespace Database\Factories;

use App\Models\MikrotikConnection;
use App\Models\RadiusAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RadiusAccount>
 */
class RadiusAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mikrotik_connection_id' => MikrotikConnection::factory(),
            'username' => fake()->unique()->userName(),
            'password' => 'secret123',
            'service' => fake()->randomElement(['pppoe', 'hotspot']),
            'ipv4_address' => fake()->optional()->ipv4(),
            'rate_limit' => fake()->optional()->randomElement(['10M/10M', '20M/20M']),
            'profile' => fake()->optional()->word(),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
            'uptime' => null,
            'caller_id' => null,
            'server_name' => null,
            'bytes_in' => null,
            'bytes_out' => null,
        ];
    }
}
