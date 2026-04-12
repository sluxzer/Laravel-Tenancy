<?php

namespace Database\Seeders;

use App\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            [
                'name' => 'Advanced Analytics',
                'key' => 'advanced_analytics',
                'description' => 'Enable advanced analytics dashboards',
                'is_enabled' => true,
                'is_public' => true,
            ],
            [
                'name' => 'Dark Mode',
                'key' => 'dark_mode',
                'description' => 'Enable dark mode in the UI',
                'is_enabled' => true,
                'is_public' => true,
            ],
            [
                'name' => 'API v2',
                'key' => 'api_v2',
                'description' => 'Enable new API version 2.0',
                'is_enabled' => false,
                'is_public' => false,
            ],
            [
                'name' => 'Multi-factor Authentication',
                'key' => 'mfa',
                'description' => 'Enable multi-factor authentication',
                'is_enabled' => true,
                'is_public' => true,
            ],
            [
                'name' => 'Real-time Notifications',
                'key' => 'realtime_notifications',
                'description' => 'Enable real-time notifications via WebSockets',
                'is_enabled' => false,
                'is_public' => false,
            ],
            [
                'name' => 'Export to Excel',
                'key' => 'excel_export',
                'description' => 'Enable Excel export functionality',
                'is_enabled' => true,
                'is_public' => true,
            ],
            [
                'name' => 'Custom Integrations',
                'key' => 'custom_integrations',
                'description' => 'Allow users to create custom integrations',
                'is_enabled' => false,
                'is_public' => false,
            ],
        ];

        foreach ($flags as $flag) {
            FeatureFlag::firstOrCreate(
                ['key' => $flag['key']],
                array_merge($flag, [
                    'metadata' => [],
                ])
            );
        }

        $this->command->info('Feature flags seeded successfully.');
    }
}
