<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        // Get all tenants, plans, and users
        $tenants = Tenant::all();
        $plans = Plan::active()->get();

        foreach ($tenants as $tenant) {
            // Create 2-5 subscriptions per tenant
            $subscriptionsCount = rand(2, 5);

            for ($i = 0; $i < $subscriptionsCount; $i++) {
                $plan = $plans->random();
                $user = User::where('id', '!=', $tenant->owner_id)
                    ->whereDoesntHave('subscriptions')
                    ->inRandomOrder()
                    ->first() ?? User::factory()->create([
                        'name' => fake()->name(),
                        'email' => fake()->unique()->safeEmail(),
                        'password' => bcrypt('password'),
                        'email_verified_at' => now(),
                    ]);

                $status = fake()->randomElement(['active', 'active', 'active', 'past_due', 'cancelled']);

                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'status' => $status,
                    'billing_cycle' => fake()->randomElement(['monthly', 'yearly']),
                    'current_period_start' => now()->startOfMonth(),
                    'current_period_end' => now()->endOfMonth()->addMonth(),
                    'trial_ends_at' => $status === 'active' ? now()->addDays(rand(1, 14)) : null,
                    'stripe_subscription_id' => 'sub_'.fake()->uuid(),
                    'metadata' => [],
                ]);

                $user->assignRole('user');
            }
        }

        $this->command->info('Subscriptions seeded successfully.');
    }
}
