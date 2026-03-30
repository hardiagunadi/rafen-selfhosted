<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WaBlastLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaBlastLog>
 */
class WaBlastLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sent_by_id' => User::factory(),
            'sent_by_name' => fake()->name(),
            'event' => 'blast',
            'target_type' => 'ppp',
            'target_id' => fake()->numberBetween(1, 999),
            'phone' => fake()->numerify('62812########'),
            'phone_normalized' => fake()->numerify('62812########'),
            'status' => 'sent',
            'reason' => null,
            'customer_name' => fake()->name(),
            'ref_id' => fake()->uuid(),
            'message' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}
