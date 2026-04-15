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
        $types = ['percentage', 'fixed'];
        $type = fake()->randomElement($types);

        return [
            'code' => strtoupper(fake()->unique()->lexify('??????')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => $type,
            'value' => match ($type) {
                'percentage' => fake()->numberBetween(5, 50),
                'fixed' => fake()->randomFloat(2, 5, 50),
            },
            'plan_id' => fake()->optional(0.5)->numberBetween(1, 3),
            'max_uses' => fake()->numberBetween(10, 1000),
            'used_count' => fake()->numberBetween(0, 500),
            'expires_at' => now()->addDays(fake()->numberBetween(30, 365)),
            'is_active' => true,  // Always active for tests
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'value' => fake()->numberBetween(5, 50),
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'value' => fake()->randomFloat(2, 5, 50),
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
            'expires_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'is_active' => false,
        ]);
    }

    public function forPlan(int $planId): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => $planId,
        ]);
    }
}
