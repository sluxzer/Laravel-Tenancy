<?php

namespace Database\Factories;

use App\Models\UsageMetric;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageMetric>
 */
class UsageMetricFactory extends Factory
{
    public function definition(): array
    {
        $metricTypes = [
            'api_calls',
            'storage',
            'bandwidth',
            'emails_sent',
            'users_active',
        ];

        return [
            'tenant_id' => fake()->numberBetween(1, 10),
            'user_id' => User::factory(),
            'metric_type' => fake()->randomElement($metricTypes),
            'metric_value' => fake()->numberBetween(1, 10000),
            'unit' => 'count',
            'period' => now()->format('Y-m'),
            'metadata' => [
                'source' => fake()->randomElement(['api', 'web', 'mobile']),
                'endpoint' => fake()->url(),
            ],
            'recorded_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function apiCalls(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'api_calls',
            'unit' => 'count',
        ]);
    }

    public function storage(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'storage',
            'unit' => 'bytes',
            'metric_value' => fake()->numberBetween(1024, 10737418240), // 1KB to 10GB
        ]);
    }

    public function bandwidth(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'bandwidth',
            'unit' => 'bytes',
            'metric_value' => fake()->numberBetween(1048576, 107374182400), // 1MB to 100GB
        ]);
    }

    public function forPeriod(string $period): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => $period,
        ]);
    }
}
