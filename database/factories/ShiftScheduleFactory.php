<?php

namespace Database\Factories;

use App\Models\ShiftDefinition;
use App\Models\ShiftSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftSchedule>
 */
class ShiftScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shift_definition_id' => ShiftDefinition::factory(),
            'schedule_date' => now()->toDateString(),
            'status' => 'scheduled',
            'notes' => null,
        ];
    }
}
