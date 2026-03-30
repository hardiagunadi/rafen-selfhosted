<?php

namespace Database\Factories;

use App\Models\ShiftSchedule;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftSwapRequest>
 */
class ShiftSwapRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'target_id' => null,
            'from_schedule_id' => ShiftSchedule::factory(),
            'to_schedule_id' => null,
            'reason' => fake()->sentence(),
            'status' => 'pending',
            'reviewed_by_id' => null,
            'reviewed_at' => null,
        ];
    }
}
