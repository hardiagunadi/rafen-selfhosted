<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\PppProfile;
use App\Models\PppUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.now()->format('Ym').fake()->unique()->numerify('####'),
            'ppp_user_id' => PppUser::factory(),
            'ppp_profile_id' => PppProfile::factory(),
            'customer_id' => fake()->unique()->numerify('############'),
            'customer_name' => fake()->name(),
            'tipe_service' => 'pppoe',
            'paket_langganan' => 'Paket '.fake()->word(),
            'harga_dasar' => 100000,
            'harga_asli' => 100000,
            'ppn_percent' => 11,
            'ppn_amount' => 11000,
            'total' => 111000,
            'promo_applied' => false,
            'prorata_applied' => false,
            'due_date' => now()->addMonth()->toDateString(),
            'status' => 'unpaid',
            'renewed_without_payment' => false,
            'payment_method' => null,
            'payment_channel' => null,
            'payment_reference' => null,
            'paid_at' => null,
            'payment_id' => null,
            'paid_by' => null,
            'cash_received' => null,
            'transfer_amount' => null,
            'payment_note' => null,
            'payment_token' => fake()->unique()->sha256(),
        ];
    }
}
