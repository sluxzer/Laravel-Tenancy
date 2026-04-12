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
            'billing_cycle' => fake()->randomElement(['monthly', 'yearly', 'quarterly']),
            'current_period_start' => fake()->dateTimeBetween('-1 month', 'now'),
            'current_period_end' => fake()->dateTimeBetween('now', '+1 month'),
            'trial_ends_at' => fake()->optional(0.7)->dateTimeBetween('now', '+14 days'),
            'cancels_at' => fake()->optional(0.1)->dateTimeBetween('+1 month', '+3 months'),
            'ends_at' => fake()->optional(0.1)->dateTimeBetween('+1 month', '+3 months'),
            'stripe_subscription_id' => fake()->optional()->uuid(),
            'metadata' => [],
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'cancels_at' => null,
            'ends_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancels_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'past_due',
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'yearly',
            'current_period_start' => now()->startOfYear(),
            'current_period_end' => now()->endOfYear(),
        ]);
    }
}
