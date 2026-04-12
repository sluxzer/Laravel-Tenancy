<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'Acme Corporation',
                'slug' => 'acme-corp',
                'domain' => 'acme.saas-tenancy.test',
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'name' => 'Tech Solutions Inc',
                'slug' => 'tech-solutions',
                'domain' => 'tech-solutions.saas-tenancy.test',
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'name' => 'Global Marketing',
                'slug' => 'global-marketing',
                'domain' => 'global-marketing.saas-tenancy.test',
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'name' => 'StartupXYZ',
                'slug' => 'startupxyz',
                'domain' => 'startupxyz.saas-tenancy.test',
                'status' => 'pending',
                'is_active' => false,
            ],
            [
                'name' => 'Suspended Company',
                'slug' => 'suspended-company',
                'domain' => 'suspended.saas-tenancy.test',
                'status' => 'suspended',
                'is_active' => false,
            ],
        ];

        foreach ($tenants as $tenantData) {
            $tenant = Tenant::firstOrCreate(
                ['slug' => $tenantData['slug']],
                array_merge($tenantData, [
                    'trial_ends_at' => now()->addDays(30),
                    'settings' => [
                        'timezone' => 'UTC',
                        'locale' => 'en',
                        'date_format' => 'Y-m-d',
                        'time_format' => 'H:i',
                    ],
                ])
            );

            // Create owner user for each tenant
            if (! $tenant->owner_id) {
                $owner = User::firstOrCreate(
                    [
                        'email' => "owner@{$tenant->slug}.com",
                    ],
                    [
                        'name' => "{$tenant->name} Owner",
                        'password' => bcrypt('password'),
                        'email_verified_at' => now(),
                    ]
                );

                $owner->assignRole('admin');
                $tenant->update(['owner_id' => $owner->id]);
            }
        }

        $this->command->info('Tenants and owners seeded successfully.');
    }
}
