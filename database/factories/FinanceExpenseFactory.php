<?php

namespace Database\Factories;

use App\Models\FinanceExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceExpense>
 */
class FinanceExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => null,
            'expense_date' => today(),
            'category' => 'Biaya Operasional',
            'service_type' => 'general',
            'amount' => 50000,
            'payment_method' => 'cash',
            'reference' => fake()->bothify('EXP-####'),
            'description' => 'Pengeluaran manual',
        ];
    }
}
