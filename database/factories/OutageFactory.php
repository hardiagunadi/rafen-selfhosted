<?php

namespace Database\Factories;

use App\Models\Outage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Outage>
 */
class OutageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Gangguan '.fake()->words(3, true),
            'description' => fake()->sentence(),
            'status' => 'open',
            'severity' => 'high',
            'started_at' => now()->subHour(),
            'estimated_resolved_at' => now()->addHours(3),
            'resolved_at' => null,
            'public_token' => fake()->unique()->regexify('[A-Fa-f0-9]{32}'),
            'wa_blast_sent_at' => null,
            'wa_blast_count' => 0,
            'resolution_wa_sent_at' => null,
            'created_by_id' => User::factory(),
            'include_status_link' => true,
        ];
    }
}
