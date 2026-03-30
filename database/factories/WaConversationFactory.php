<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WaConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaConversation>
 */
class WaConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => 'selfhosted-'.fake()->slug(),
            'contact_phone' => fake()->unique()->numerify('62812########'),
            'contact_name' => fake()->name(),
            'assigned_to_id' => User::factory(),
            'status' => 'open',
            'bot_paused_until' => null,
            'last_message' => fake()->sentence(),
            'last_message_at' => now(),
            'unread_count' => 0,
        ];
    }
}
