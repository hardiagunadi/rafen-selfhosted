<?php

namespace Database\Factories;

use App\Models\Outage;
use App\Models\OutageUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutageUpdate>
 */
class OutageUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'outage_id' => Outage::factory(),
            'user_id' => User::factory(),
            'type' => 'note',
            'body' => fake()->sentence(),
            'meta' => null,
            'is_public' => true,
        ];
    }
}
