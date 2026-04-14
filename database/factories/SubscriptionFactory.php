<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fake()->numberBetween(1, 10),
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'status' => fake()->randomElement(['active', 'past_due', 'cancelled', 'expired', 'paused']),
            'starts_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'ends_at' => fake()->dateTimeBetween('now', '+1 month'),
            'trial_ends_at' => fake()->optional(0.7)->dateTimeBetween('now', '+14 days'),
            'cancelled_at' => fake()->optional(0.1)->dateTimeBetween('now', '+1 month'),
            'grace_period_ends_at' => fake()->optional(0.1)->dateTimeBetween('+1 month', '+3 months'),
            'metadata' => [],
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'cancelled_at' => null,
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'past_due',
        ]);
    }
}
