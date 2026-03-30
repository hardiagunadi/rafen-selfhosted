<?php

namespace Database\Factories;

use App\Models\TeknisiSetoran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeknisiSetoran>
 */
class TeknisiSetoranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teknisi_id' => User::factory()->state([
                'role' => 'teknisi',
            ]),
            'verified_by' => null,
            'period_date' => now()->toDateString(),
            'total_invoices' => 0,
            'total_tagihan' => 0,
            'total_cash' => 0,
            'status' => 'draft',
            'submitted_at' => null,
            'verified_at' => null,
            'notes' => null,
        ];
    }
}
