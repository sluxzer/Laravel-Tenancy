<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin role
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'Full system access',
            ]
        );

        // Create user role
        $userRole = Role::firstOrCreate(
            ['name' => 'user'],
            [
                'display_name' => 'User',
                'description' => 'Standard user access',
            ]
        );

        // Create permissions
        $permissions = [
            // User management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            // Subscription management
            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.edit',
            'subscriptions.cancel',
            // Invoice management
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',
            // Payment management
            'payments.view',
            'payments.create',
            'payments.refund',
            // Tenant management (admin only)
            'tenants.view',
            'tenants.create',
            'tenants.edit',
            'tenants.delete',
            'tenants.suspend',
            'tenants.activate',
            // Plan management (admin only)
            'plans.view',
            'plans.create',
            'plans.edit',
            'plans.delete',
            // Analytics
            'analytics.view',
            // Reports
            'reports.view',
            'reports.create',
            'reports.delete',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // Attach all permissions to admin role
        $adminRole->syncPermissions(Permission::all());

        // Attach basic permissions to user role
        $userPermissions = [
            'subscriptions.view',
            'subscriptions.cancel',
            'invoices.view',
            'payments.view',
            'analytics.view',
            'reports.view',
        ];
        $userRole->syncPermissions($userPermissions);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');

        // Create demo user
        $demoUser = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $demoUser->assignRole('user');

        $this->command->info('Admin roles, permissions, and users seeded successfully.');
    }
}
