<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Sync Exchange Rates Command
 *
 * CLI command to sync exchange rates from external API.
 */
class SyncExchangeRatesCommand extends Command
{
    protected $signature = 'exchange:sync {--source=fixer}';

    protected $description = 'Sync exchange rates from external source';

    public function handle(): int
    {
        $source = $this->option('source', 'fixer.io');

        $this->info("Syncing exchange rates from {$source}...");

        // Fetch rates from external API
        $rates = $this->fetchRatesFromApi($source);

        if (empty($rates)) {
            $this->warn("No rates fetched from {$source}");

            return 0;
        }

        // Update or create rates in database
        foreach ($rates as $rateData) {
            $fromCurrency = Currency::firstWhere('code', $rateData['from_currency']);
            $toCurrency = Currency::firstWhere('code', $rateData['to_currency']);

            if (! $fromCurrency || ! $toCurrency) {
                $this->warn("Skipping rate: missing currencies for {$rateData['from_currency']}->{$rateData['to_currency']}");

                continue;
            }

            ExchangeRate::updateOrCreate(
                [
                    'from_currency_id' => $fromCurrency->id,
                    'to_currency_id' => $toCurrency->id,
                ],
                [
                    'rate' => $rateData['rate'],
                    'source' => $source,
                ]
            );
        }

        $this->info('Synced '.count($rates).' exchange rates');

        return 0;
    }

    /**
     * Fetch rates from external API.
     */
    protected function fetchRatesFromApi(string $source): array
    {
        $response = Http::get("https://api.{$source}.io/rates");

        if (! $response->successful()) {
            $this->error("Failed to fetch rates from {$source}");

            return [];
        }

        return $response->json();
    }
}
