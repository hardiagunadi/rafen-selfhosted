<?php

namespace Database\Factories;

use App\Models\RadiusNas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RadiusNas>
 */
class RadiusNasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'NAS '.fake()->unique()->citySuffix(),
            'shortname' => fake()->unique()->slug(2, false),
            'ip_address' => fake()->ipv4(),
            'secret' => fake()->bothify('secret-####'),
            'require_message_authenticator' => true,
            'auth_port' => 1812,
            'acct_port' => 1813,
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
