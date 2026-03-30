<?php

namespace Database\Factories;

use App\Models\Outage;
use App\Models\OutageAffectedArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutageAffectedArea>
 */
class OutageAffectedAreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'outage_id' => Outage::factory(),
            'area_type' => 'keyword',
            'label' => fake()->city(),
        ];
    }
}
