<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => 'created',
            'subject_type' => 'Invoice',
            'subject_id' => fake()->numberBetween(1, 999),
            'subject_label' => 'INV-'.fake()->numerify('########'),
            'properties' => ['source' => 'factory'],
            'ip_address' => fake()->ipv4(),
            'created_at' => now(),
        ];
    }
}
