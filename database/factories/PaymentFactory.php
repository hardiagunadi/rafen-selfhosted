<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_number' => 'PAY-'.now()->format('Ymd').'-'.strtoupper(fake()->bothify('??##??')),
            'payment_type' => 'invoice',
            'invoice_id' => Invoice::factory(),
            'payment_channel' => 'manual_cash',
            'payment_method' => 'cash',
            'amount' => 111000,
            'fee' => 0,
            'total_amount' => 111000,
            'status' => 'paid',
            'reference' => fake()->uuid(),
            'merchant_ref' => null,
            'expired_at' => null,
            'paid_at' => now(),
            'callback_data' => null,
            'notes' => null,
        ];
    }
}
