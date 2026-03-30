<?php

namespace Database\Factories;

use App\Models\WaKeywordRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaKeywordRule>
 */
class WaKeywordRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'keywords' => ['gangguan', 'internet'],
            'reply_text' => fake()->sentence(),
            'priority' => 0,
            'is_active' => true,
        ];
    }
}
