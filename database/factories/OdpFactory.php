<?php

namespace Database\Factories;

use App\Models\Odp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Odp>
 */
class OdpFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'ODP-'.fake()->unique()->numerify('###'),
            'name' => 'ODP '.fake()->citySuffix(),
            'area' => fake()->city(),
            'latitude' => fake()->latitude(-11, 6),
            'longitude' => fake()->longitude(95, 141),
            'capacity_ports' => fake()->numberBetween(8, 32),
            'status' => 'active',
            'notes' => fake()->sentence(),
        ];
    }
}
