<?php

namespace Database\Factories;

use App\Models\MikrotikConnection;
use App\Models\ProfileGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfileGroup>
 */
class ProfileGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'GROUP-'.fake()->unique()->bothify('##'),
            'mikrotik_connection_id' => MikrotikConnection::factory(),
            'type' => 'pppoe',
            'ip_pool_mode' => 'group_only',
            'ip_pool_name' => 'POOL-'.fake()->bothify('##'),
            'ip_address' => null,
            'netmask' => null,
            'range_start' => null,
            'range_end' => null,
            'dns_servers' => null,
            'parent_queue' => null,
            'host_min' => null,
            'host_max' => null,
        ];
    }
}
