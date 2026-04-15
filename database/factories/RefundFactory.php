<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Refund;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'transaction_id' => Transaction::factory(),
            'invoice_id' => Invoice::factory(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'reason' => fake()->randomElement(['requested_by_customer', 'duplicate', 'fraudulent', 'other']),
            'status' => fake()->randomElement(['pending', 'processed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
            'processed_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'processed_by' => fake()->optional()->numberBetween(1, 10),
        ];
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processed',
            'processed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'processed_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function forTransaction(int $transactionId): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_id' => $transactionId,
        ]);
    }

    public function forInvoice(int $invoiceId): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoiceId,
        ]);
    }
}
