<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    public function definition(): array
    {
        $types = ['percentage', 'fixed_amount', 'free_trial'];
        $type = fake()->randomElement($types);

        return [
            'code' => strtoupper(fake()->unique()->lexify('??????')),
            'type' => $type,
            'value' => match ($type) {
                'percentage' => fake()->numberBetween(5, 50),
                'fixed_amount' => fake()->randomFloat(2, 5, 50),
                'free_trial' => fake()->numberBetween(7, 30),
            },
            'currency_code' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'description' => fake()->sentence(),
            'max_uses' => fake()->numberBetween(10, 1000),
            'used_count' => fake()->numberBetween(0, 500),
            'valid_from' => now()->subDays(fake()->numberBetween(1, 30)),
            'valid_until' => now()->addDays(fake()->numberBetween(30, 365)),
            'is_active' => fake()->boolean(80),
            'plan_ids' => fake()->randomElements([1, 2, 3], fake()->numberBetween(0, 3)),
            'min_amount' => fake()->optional(0.3)->randomFloat(2, 10, 50),
            'max_discount' => fake()->optional(0.3)->randomFloat(2, 50, 100),
            'metadata' => [],
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'value' => fake()->numberBetween(5, 50),
        ]);
    }

    public function fixedAmount(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed_amount',
            'value' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    public function freeTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'free_trial',
            'value' => fake()->numberBetween(7, 30),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_until' => now()->subDays(fake()->numberBetween(1, 30)),
            'is_active' => false,
        ]);
    }

    public function forPlan(int $planId): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_ids' => [$planId],
        ]);
    }
}
