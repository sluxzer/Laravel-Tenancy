<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fake()->numberBetween(1, 10),
            'user_id' => User::factory(),
            'invoice_id' => Invoice::factory(),
            'subscription_id' => Subscription::factory(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency_code' => 'USD',
            'payment_method' => fake()->randomElement(['card', 'bank_transfer', 'paypal', 'xendit']),
            'gateway' => fake()->randomElement(['stripe', 'paypal', 'xendit']),
            'transaction_id' => fake()->uuid(),
            'status' => fake()->randomElement(['pending', 'processing', 'paid', 'failed', 'cancelled', 'refunded']),
            'payment_token' => fake()->optional()->uuid(),
            'metadata' => [
                'card_last4' => fake()->numerify('####'),
                'card_brand' => fake()->randomElement(['visa', 'mastercard', 'amex']),
            ],
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function withStripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'stripe',
            'payment_method' => 'card',
            'metadata' => [
                'card_last4' => fake()->numerify('####'),
                'card_brand' => fake()->randomElement(['visa', 'mastercard']),
            ],
        ]);
    }

    public function withPayPal(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'paypal',
            'payment_method' => 'paypal',
        ]);
    }
}
