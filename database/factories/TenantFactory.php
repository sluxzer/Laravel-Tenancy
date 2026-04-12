<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'domain' => fake()->unique()->domainName(),
            'status' => fake()->randomElement(['active', 'suspended', 'pending']),
            'is_active' => fake()->boolean(),
            'trial_ends_at' => now()->addDays(fake()->numberBetween(1, 30)),
            'settings' => [
                'timezone' => fake()->timezone(),
                'locale' => fake()->randomElement(['en', 'es', 'fr', 'de']),
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
            ],
            'owner_id' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
            'is_active' => false,
        ]);
    }

    public function withOwner(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $userId,
        ]);
    }
}
