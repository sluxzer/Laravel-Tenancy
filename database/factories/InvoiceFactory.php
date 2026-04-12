<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fake()->numberBetween(1, 10),
            'user_id' => User::factory(),
            'subscription_id' => Subscription::factory(),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency_code' => 'USD',
            'status' => fake()->randomElement(['draft', 'unpaid', 'paid', 'overdue', 'void']),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'paid_at' => fake()->optional(0.6)->dateTimeBetween('-1 month', 'now'),
            'items' => [
                [
                    'description' => fake()->word(),
                    'quantity' => fake()->numberBetween(1, 10),
                    'unit_price' => fake()->randomFloat(2, 10, 100),
                    'total' => 0,
                ],
            ],
            'tax_amount' => fake()->randomFloat(2, 0, 50),
            'discount_amount' => fake()->optional(0.3)->randomFloat(2, 0, 20),
            'total_amount' => 0, // Calculated from items
            'notes' => fake()->optional()->sentence(),
            'metadata' => [],
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
            'status' => 'unpaid',
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
}
