<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 1000);
        $taxAmount = fake()->randomFloat(2, 0, $subtotal * 0.2);
        $discountAmount = fake()->optional(0.3)->randomFloat(2, 0, $subtotal * 0.1);

        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'subscription_id' => Subscription::factory(),
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount ?? 0,
            'total_amount' => $subtotal + $taxAmount - ($discountAmount ?? 0),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'status' => fake()->randomElement(['pending', 'paid', 'overdue', 'cancelled']),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'paid_at' => fake()->optional(0.6)->dateTimeBetween('-1 month', 'now'),
            'cancelled_at' => fake()->optional(0.1)->dateTimeBetween('-30 days', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'paid_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    public function forSubscription(int $subscriptionId): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_id' => $subscriptionId,
        ]);
    }
}
