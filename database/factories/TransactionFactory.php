<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'invoice_id' => Invoice::factory(),
            'subscription_id' => Subscription::factory(),
            'type' => fake()->randomElement(['payment', 'refund', 'credit', 'debit']),
            'provider' => fake()->randomElement(['stripe', 'paypal', 'xendit', 'manual']),
            'provider_transaction_id' => fake()->optional()->uuid(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'status' => fake()->randomElement(['pending', 'completed', 'failed']),
            'description' => fake()->optional()->sentence(),
            'metadata' => fake()->optional()->randomElement([
                ['card_last4' => fake()->numerify('####'), 'card_brand' => fake()->randomElement(['visa', 'mastercard'])],
                null,
            ]),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    public function payment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'payment',
        ]);
    }

    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'refund',
        ]);
    }

    public function forInvoice(int $invoiceId): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoiceId,
        ]);
    }

    public function withProvider(string $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
        ]);
    }
}
