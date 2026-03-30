<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'voucher',
            'username' => strtoupper(fake()->bothify('VC##??')),
            'plan_name' => 'Voucher Hotspot',
            'amount' => 5000,
            'tax_amount' => 0,
            'total' => 5000,
            'status' => 'paid',
            'payment_method' => 'cash',
            'paid_at' => now(),
            'period_start' => today(),
            'period_end' => today()->addDay(),
            'mixradius_id' => null,
            'notes' => 'Voucher internal',
        ];
    }
}
