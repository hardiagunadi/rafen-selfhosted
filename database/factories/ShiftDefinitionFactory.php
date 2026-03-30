<?php

namespace Database\Factories;

use App\Models\ShiftDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftDefinition>
 */
class ShiftDefinitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Shift '.fake()->unique()->bothify('??'),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'role' => null,
            'color' => '#3b82f6',
            'is_active' => true,
        ];
    }
}
