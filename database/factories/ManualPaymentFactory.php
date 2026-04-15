<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\ManualPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManualPayment>
 */
class ManualPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'invoice_id' => Invoice::factory(),
            'method' => fake()->randomElement(['bank_transfer', 'cash', 'check', 'other']),
            'reference' => fake()->optional()->numerify('REF-########'),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'status' => fake()->randomElement(['pending', 'processed', 'rejected']),
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

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'processed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function forInvoice(int $invoiceId): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoiceId,
        ]);
    }
}
