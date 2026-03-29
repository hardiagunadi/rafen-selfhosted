<?php

namespace Database\Factories;

use App\Models\MikrotikConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MikrotikConnection>
 */
class MikrotikConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Router '.fake()->city(),
            'host' => fake()->ipv4(),
            'api_port' => 8728,
            'api_ssl_port' => 8729,
            'use_ssl' => false,
            'username' => 'admin',
            'password' => 'secret123',
            'radius_secret' => 'radius123',
            'ros_version' => 'auto',
            'api_timeout' => 10,
            'notes' => fake()->sentence(),
            'is_active' => true,
            'is_online' => null,
            'last_ping_latency_ms' => null,
            'last_ping_at' => null,
            'failed_ping_count' => 0,
            'ping_unstable' => false,
            'last_port_open' => null,
            'last_ping_message' => null,
            'auth_port' => 1812,
            'acct_port' => 1813,
            'timezone' => '+07:00 Asia/Jakarta',
            'isolir_url' => null,
            'isolir_setup_done' => false,
            'isolir_pool_name' => null,
            'isolir_pool_range' => null,
            'isolir_gateway' => null,
            'isolir_profile_name' => null,
            'isolir_rate_limit' => null,
            'isolir_setup_at' => null,
            'hotspot_subnet' => null,
        ];
    }
}
