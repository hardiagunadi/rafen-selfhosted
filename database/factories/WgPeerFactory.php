<?php

namespace Database\Factories;

use App\Models\WgPeer;
use App\Services\WgKeyService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WgPeer>
 */
class WgPeerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $keys = app(WgKeyService::class)->generateKeypair();

        return [
            'name' => fake()->unique()->company(),
            'public_key' => $keys['public_key'],
            'private_key' => $keys['private_key'],
            'preshared_key' => null,
            'vpn_ip' => '10.0.0.'.fake()->unique()->numberBetween(2, 254),
            'extra_allowed_ips' => null,
            'is_active' => true,
            'last_synced_at' => null,
        ];
    }
}
