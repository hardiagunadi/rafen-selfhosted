<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WaChatMessage;
use App\Models\WaConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaChatMessage>
 */
class WaChatMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => WaConversation::factory(),
            'direction' => 'inbound',
            'message' => fake()->sentence(),
            'media_type' => null,
            'media_path' => null,
            'media_mime' => null,
            'media_filename' => null,
            'sender_name' => fake()->name(),
            'sender_id' => User::factory(),
            'wa_message_id' => 'wamid.'.fake()->uuid(),
            'created_at' => now(),
        ];
    }
}
