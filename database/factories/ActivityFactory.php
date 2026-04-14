<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['user.login', 'user.logout', 'subscription.created', 'payment.processed']),
            'description' => fake()->sentence(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => [],
            'created_at' => fake()->dateTime(),
        ];
    }

    public function login(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'user.login',
            'description' => 'User logged in',
        ]);
    }

    public function logout(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'user.logout',
            'description' => 'User logged out',
        ]);
    }

    public function subscriptionCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'subscription.created',
            'description' => 'New subscription created',
        ]);
    }

    public function paymentFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'payment.failed',
            'description' => 'Payment processing failed',
        ]);
    }

    public function forUser(int|User $user): static
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
