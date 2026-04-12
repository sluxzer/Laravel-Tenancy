<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        $events = [
            'user.login',
            'user.logout',
            'user.profile.updated',
            'user.password.changed',
            'subscription.created',
            'subscription.upgraded',
            'subscription.downgraded',
            'subscription.cancelled',
            'invoice.paid',
            'invoice.created',
            'payment.processed',
            'payment.failed',
            'admin.tenant.created',
            'admin.tenant.suspended',
        ];

        return [
            'tenant_id' => fake()->numberBetween(1, 10),
            'causer_type' => User::class,
            'causer_id' => User::factory(),
            'subject_type' => fake()->randomElement([User::class, Subscription::class, Invoice::class]),
            'subject_id' => fake()->numberBetween(1, 100),
            'description' => fake()->sentence(),
            'event' => fake()->randomElement($events),
            'properties' => [
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'metadata' => fake()->randomElement(['key' => 'value']),
            ],
            'batch_uuid' => fake()->optional()->uuid(),
        ];
    }

    public function login(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'user.login',
            'description' => 'User logged in',
        ]);
    }

    public function subscriptionCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'subscription.created',
            'description' => 'New subscription created',
        ]);
    }

    public function paymentFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'payment.failed',
            'description' => 'Payment processing failed',
        ]);
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'causer_id' => $userId,
        ]);
    }
}
