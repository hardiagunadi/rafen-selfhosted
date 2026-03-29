<?php

namespace Database\Factories;

use App\Models\WaGatewaySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaGatewaySetting>
 */
class WaGatewaySettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_name' => 'Rafen Self-Hosted',
            'business_phone' => '081234567890',
            'default_test_recipient' => '081234567890',
            'gateway_url' => 'http://127.0.0.1:3100',
            'auth_token' => 'test-token',
            'master_key' => 'test-master-key',
            'is_enabled' => true,
        ];
    }
}
