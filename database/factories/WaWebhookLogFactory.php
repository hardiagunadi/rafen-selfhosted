<?php

namespace Database\Factories;

use App\Models\WaWebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaWebhookLog>
 */
class WaWebhookLogFactory extends Factory
{
    protected $model = WaWebhookLog::class;

    public function definition(): array
    {
        return [
            'event_type' => fake()->randomElement(['meta_message', 'meta_status']),
            'session_id' => fake()->uuid(),
            'sender' => '62812'.fake()->numerify('#######'),
            'message' => fake()->sentence(),
            'status' => fake()->randomElement(['text', 'sent', 'delivered']),
            'payload' => ['example' => true],
        ];
    }
}
