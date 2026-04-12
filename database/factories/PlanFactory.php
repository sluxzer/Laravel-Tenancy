<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        $plans = [
            ['name' => 'Starter', 'price_monthly' => 9.99, 'price_yearly' => 99.90, 'features' => ['1000 API calls', '1GB storage', 'Email support']],
            ['name' => 'Professional', 'price_monthly' => 29.99, 'price_yearly' => 299.90, 'features' => ['10000 API calls', '10GB storage', 'Priority support', 'Advanced analytics']],
            ['name' => 'Enterprise', 'price_monthly' => 99.99, 'price_yearly' => 999.90, 'features' => ['Unlimited API calls', '100GB storage', '24/7 support', 'Custom integrations', 'SLA guarantee']],
        ];

        $plan = fake()->randomElement($plans);

        return [
            'name' => $plan['name'],
            'slug' => strtolower(str_replace(' ', '-', $plan['name'])),
            'description' => fake()->sentence(),
            'price_monthly' => $plan['price_monthly'],
            'price_yearly' => $plan['price_yearly'],
            'currency_code' => 'USD',
            'trial_days' => fake()->numberBetween(7, 14),
            'max_users' => fake()->numberBetween(1, 10),
            'max_projects' => fake()->numberBetween(1, 50),
            'features' => $plan['features'],
            'is_active' => fake()->boolean(80),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
