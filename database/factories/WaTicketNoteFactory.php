<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WaTicket;
use App\Models\WaTicketNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaTicketNote>
 */
class WaTicketNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => WaTicket::factory(),
            'user_id' => User::factory(),
            'note' => fake()->sentence(),
            'type' => 'note',
            'meta' => null,
            'read_by_cs' => false,
        ];
    }
}
