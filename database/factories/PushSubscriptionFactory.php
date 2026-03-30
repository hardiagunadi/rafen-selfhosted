<?php

namespace Database\Factories;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PushSubscription>
 */
class PushSubscriptionFactory extends Factory
{
    protected $model = PushSubscription::class;

    public function definition(): array
    {
        return [
            'subscribable_type' => User::class,
            'subscribable_id' => User::factory(),
            'endpoint' => 'https://push.example.test/subscription/'.fake()->unique()->uuid(),
            'public_key' => fake()->sha1(),
            'auth_token' => fake()->sha1(),
        ];
    }
}
