<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small teams and startups getting started.',
                'price_monthly' => 9.99,
                'price_yearly' => 99.90,
                'currency_code' => 'USD',
                'trial_days' => 14,
                'max_users' => 5,
                'max_projects' => 5,
                'features' => [
                    ['name' => 'API Calls', 'value' => '1,000/month', 'limit' => 1000],
                    ['name' => 'Storage', 'value' => '1 GB', 'limit' => 1073741824],
                    ['name' => 'Email Support', 'value' => 'Yes', 'limit' => null],
                    ['name' => 'Custom Domain', 'value' => 'No', 'limit' => null],
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Ideal for growing businesses with advanced features.',
                'price_monthly' => 29.99,
                'price_yearly' => 299.90,
                'currency_code' => 'USD',
                'trial_days' => 14,
                'max_users' => 20,
                'max_projects' => 50,
                'features' => [
                    ['name' => 'API Calls', 'value' => '10,000/month', 'limit' => 10000],
                    ['name' => 'Storage', 'value' => '10 GB', 'limit' => 10737418240],
                    ['name' => 'Priority Support', 'value' => 'Yes', 'limit' => null],
                    ['name' => 'Custom Domain', 'value' => 'Yes', 'limit' => null],
                    ['name' => 'Advanced Analytics', 'value' => 'Yes', 'limit' => null],
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large organizations with unlimited scale.',
                'price_monthly' => 99.99,
                'price_yearly' => 999.90,
                'currency_code' => 'USD',
                'trial_days' => 30,
                'max_users' => null,
                'max_projects' => null,
                'features' => [
                    ['name' => 'API Calls', 'value' => 'Unlimited', 'limit' => null],
                    ['name' => 'Storage', 'value' => '100 GB', 'limit' => 107374182400],
                    ['name' => '24/7 Support', 'value' => 'Yes', 'limit' => null],
                    ['name' => 'Custom Domain', 'value' => 'Yes', 'limit' => null],
                    ['name' => 'Advanced Analytics', 'value' => 'Yes', 'limit' => null],
                    ['name' => 'Custom Integrations', 'value' => 'Yes', 'limit' => null],
                    ['name' => 'SLA Guarantee', 'value' => '99.9%', 'limit' => null],
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('Plans seeded successfully.');
    }
}
