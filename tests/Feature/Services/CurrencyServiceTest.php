<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\CurrencyService;

use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    Currency::query()->delete();
    ExchangeRate::query()->delete();
});

it('can get exchange rate', function () {
    $service = new CurrencyService;
    ExchangeRate::factory()->create([
        'from_currency' => 'EUR',
        'to_currency' => 'USD',
        'rate' => 1.09,
    ]);

    $rate = $service->getExchangeRate('EUR', 'USD');

    expect($rate)->toBe(1.09);
});

it('can convert currency', function () {
    $service = new CurrencyService;
    ExchangeRate::factory()->create([
        'from_currency' => 'EUR',
        'to_currency' => 'USD',
        'rate' => 1.09,
    ]);

    $converted = $service->convert(100, 'EUR', 'USD');

    expect($converted)->toBe(109.0);
});

it('can calculate tax amount', function () {
    $service = new CurrencyService;

    $tax = $service->calculateTax(100, 'US', 0.10);

    expect($tax)->toBe(10.0);
});

it('can format currency amount', function () {
    $service = new CurrencyService;

    $formatted = $service->format(1234.56, 'USD');

    expect($formatted)->toBe('$1,234.56');
});

it('can get supported currencies', function () {
    $service = new CurrencyService;
    Currency::factory()->count(3)->create(['is_active' => true]);
    Currency::factory()->create(['is_active' => false]);

    $currencies = $service->getSupportedCurrencies();

    expect($currencies)->toHaveCount(3);
});

it('can create exchange rate', function () {
    $service = new CurrencyService;

    $rate = $service->createExchangeRate('GBP', 'USD', 1.27);

    expect($rate->from_currency)->toBe('GBP');
    expect($rate->to_currency)->toBe('USD');
    expect($rate->rate)->toBe(1.27);
});

it('can update exchange rate', function () {
    $service = new CurrencyService;
    $rate = ExchangeRate::factory()->create([
        'from_currency' => 'EUR',
        'to_currency' => 'USD',
        'rate' => 1.09,
    ]);

    $updated = $service->updateExchangeRate($rate, 1.12);

    expect($updated->rate)->toBe(1.12);
});

it('can delete exchange rate', function () {
    $service = new CurrencyService;
    $rate = ExchangeRate::factory()->create();

    $service->deleteExchangeRate($rate);

    assertDatabaseMissing('exchange_rates', ['id' => $rate->id]);
});
