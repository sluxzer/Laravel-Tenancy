<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    public function definition(): array
    {
        $types = [
            'subscription.renewed',
            'subscription.expiring',
            'subscription.cancelled',
            'invoice.paid',
            'invoice.overdue',
            'payment.failed',
            'user.invited',
            'system.maintenance',
        ];

        return [
            'tenant_id' => fake()->numberBetween(1, 10),
            'user_id' => User::factory(),
            'type' => fake()->randomElement($types),
            'title' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'data' => [
                'action_url' => fake()->url(),
                'action_text' => 'View Details',
            ],
            'is_read' => fake()->boolean(60),
            'read_at' => fake()->optional(0.6)->dateTimeBetween('-1 week', 'now'),
            'sent_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function subscriptionRenewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'subscription.renewed',
            'title' => 'Subscription Renewed',
            'message' => 'Your subscription has been successfully renewed.',
        ]);
    }

    public function invoiceOverdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'invoice.overdue',
            'title' => 'Invoice Overdue',
            'message' => 'Your invoice is now overdue. Please make a payment to avoid service interruption.',
        ]);
    }
}
