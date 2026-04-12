<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        $vouchers = [
            [
                'code' => 'WELCOME10',
                'type' => 'percentage',
                'value' => 10,
                'currency_code' => 'USD',
                'description' => 'Welcome discount for new customers',
                'max_uses' => 100,
                'valid_from' => now()->subDay(),
                'valid_until' => now()->addMonths(6),
                'is_active' => true,
            ],
            [
                'code' => 'STARTUP20',
                'type' => 'percentage',
                'value' => 20,
                'currency_code' => 'USD',
                'description' => 'Special offer for startup plans',
                'max_uses' => 50,
                'min_amount' => 50,
                'valid_from' => now()->subDay(),
                'valid_until' => now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'SUMMER50',
                'type' => 'fixed_amount',
                'value' => 50,
                'currency_code' => 'USD',
                'description' => 'Summer promotion - $50 off',
                'max_uses' => 200,
                'min_amount' => 100,
                'valid_from' => now()->subDay(),
                'valid_until' => now()->addMonths(2),
                'is_active' => true,
            ],
            [
                'code' => 'TRIAL30',
                'type' => 'free_trial',
                'value' => 30,
                'currency_code' => 'USD',
                'description' => '30-day free trial',
                'max_uses' => 1000,
                'valid_from' => now()->subDay(),
                'valid_until' => now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'EXPIRED25',
                'type' => 'percentage',
                'value' => 25,
                'currency_code' => 'USD',
                'description' => 'Expired voucher (for testing)',
                'max_uses' => 50,
                'valid_from' => now()->subMonths(3),
                'valid_until' => now()->subMonth(),
                'is_active' => false,
            ],
        ];

        foreach ($vouchers as $voucher) {
            Voucher::firstOrCreate(
                ['code' => $voucher['code']],
                array_merge($voucher, [
                    'used_count' => 0,
                    'plan_ids' => null,
                    'max_discount' => null,
                    'metadata' => [],
                ])
            );
        }

        $this->command->info('Vouchers seeded successfully.');
    }
}
