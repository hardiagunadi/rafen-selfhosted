<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WaTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaTicket>
 */
class WaTicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('62812########'),
            'customer_type' => 'ppp',
            'customer_id' => fake()->numberBetween(1, 999),
            'title' => 'Gangguan koneksi '.fake()->word(),
            'description' => fake()->sentence(10),
            'type' => 'complaint',
            'status' => 'open',
            'priority' => 'normal',
            'assigned_to_id' => User::factory(),
            'assigned_by_id' => User::factory(),
            'resolved_at' => null,
            'public_token' => bin2hex(random_bytes(16)),
        ];
    }
}
