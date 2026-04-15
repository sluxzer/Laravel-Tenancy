<?php

namespace Database\Factories;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fake()->numberBetween(1, 10),
            'name' => fake()->company().' Webhook',
            'url' => fake()->url(),
            'events' => fake()->randomElements([
                'subscription.created',
                'subscription.renewed',
                'subscription.cancelled',
                'invoice.paid',
                'invoice.overdue',
                'payment.failed',
                'payment.succeeded',
                'user.created',
                'user.deleted',
            ], fake()->numberBetween(1, 5)),
            'secret' => fake()->uuid(),
            'is_active' => fake()->boolean(80),
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'SaaS-Tenancy-Webhook',
            ],
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

    public function withEvents(array $events): static
    {
        return $this->state(fn (array $attributes) => [
            'events' => $events,
        ]);
    }
}
