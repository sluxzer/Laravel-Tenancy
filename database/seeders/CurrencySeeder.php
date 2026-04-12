<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'is_active' => true],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_active' => true],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'is_active' => true],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$', 'is_active' => true],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'is_active' => true],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'is_active' => true],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'is_active' => true],
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱', 'is_active' => true],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        // Create exchange rates (base: USD)
        $exchangeRates = [
            ['from_currency' => 'EUR', 'to_currency' => 'USD', 'rate' => 1.09],
            ['from_currency' => 'GBP', 'to_currency' => 'USD', 'rate' => 1.27],
            ['from_currency' => 'CAD', 'to_currency' => 'USD', 'rate' => 0.74],
            ['from_currency' => 'AUD', 'to_currency' => 'USD', 'rate' => 0.66],
            ['from_currency' => 'JPY', 'to_currency' => 'USD', 'rate' => 0.0067],
            ['from_currency' => 'IDR', 'to_currency' => 'USD', 'rate' => 0.000065],
            ['from_currency' => 'PHP', 'to_currency' => 'USD', 'rate' => 0.018],
        ];

        foreach ($exchangeRates as $rate) {
            ExchangeRate::firstOrCreate(
                ['from_currency' => $rate['from_currency'], 'to_currency' => $rate['to_currency']],
                array_merge($rate, ['effective_date' => now()])
            );
        }

        $this->command->info('Currencies and exchange rates seeded successfully.');
    }
}
