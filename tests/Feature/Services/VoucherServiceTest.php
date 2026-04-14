<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\User;
use App\Models\Voucher;
use App\Services\VoucherService;

use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    Voucher::query()->delete();
    Plan::query()->delete();
    User::query()->delete();
});

it('can validate a valid voucher', function () {
    $service = new VoucherService;
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price_monthly' => 100]);
    $voucher = Voucher::factory()->create([
        'type' => 'percentage',
        'value' => 20,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addMonth(),
        'is_active' => true,
    ]);

    $result = $service->validate($voucher->code, $user);

    expect($result['valid'])->toBeTrue();
});

it('can invalidate an expired voucher', function () {
    $service = new VoucherService;
    $user = User::factory()->create();
    $voucher = Voucher::factory()->create([
        'type' => 'percentage',
        'value' => 20,
        'valid_from' => now()->subMonths(3),
        'valid_until' => now()->subMonth(),
        'is_active' => true,
    ]);

    $result = $service->validate($voucher->code, $user);

    expect($result['valid'])->toBeFalse();
    expect($result['message'])->toContain('expired');
});

it('can invalidate an inactive voucher', function () {
    $service = new VoucherService;
    $user = User::factory()->create();
    $voucher = Voucher::factory()->create([
        'is_active' => false,
    ]);

    $result = $service->validate($voucher->code, $user);

    expect($result['valid'])->toBeFalse();
    expect($result['message'])->toContain('not active');
});

it('can invalidate a voucher with max uses reached', function () {
    $service = new VoucherService;
    $user = User::factory()->create();
    $voucher = Voucher::factory()->create([
        'max_uses' => 5,
        'used_count' => 5,
        'is_active' => true,
    ]);

    $result = $service->validate($voucher->code, $user);

    expect($result['valid'])->toBeFalse();
    expect($result['message'])->toContain('maximum uses');
});

it('can apply percentage voucher', function () {
    $service = new VoucherService;
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price_monthly' => 100]);
    $voucher = Voucher::factory()->percentage()->create([
        'max_uses' => 100,
        'is_active' => true,
    ]);

    $result = $service->apply($voucher->code, $user, 100);

    expect($result['discount_amount'])->toBe(20.0);
    expect($result['final_amount'])->toBe(80.0);
});

it('can apply fixed amount voucher', function () {
    $service = new VoucherService;
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price_monthly' => 100]);
    $voucher = Voucher::factory()->fixedAmount()->create([
        'max_uses' => 100,
        'is_active' => true,
    ]);

    $result = $service->apply($voucher->code, $user, 100);

    expect($result['discount_amount'])->toBe(50.0);
    expect($result['final_amount'])->toBe(50.0);
});

it('can apply free trial voucher', function () {
    $service = new VoucherService;
    $user = User::factory()->create();
    $voucher = Voucher::factory()->freeTrial()->create([
        'max_uses' => 100,
        'is_active' => true,
    ]);

    $result = $service->apply($voucher->code, $user, 100);

    expect($result['trial_days'])->toBe(30);
});

it('can create a voucher', function () {
    $service = new VoucherService;

    $voucher = $service->create([
        'code' => 'TEST2024',
        'type' => 'percentage',
        'value' => 25,
        'currency_code' => 'USD',
        'max_uses' => 100,
    ]);

    expect($voucher->code)->toBe('TEST2024');
    expect($voucher->type)->toBe('percentage');
    expect($voucher->value)->toBe(25);
});

it('can update a voucher', function () {
    $service = new VoucherService;
    $voucher = Voucher::factory()->create();

    $updated = $service->update($voucher, [
        'value' => 30,
        'is_active' => false,
    ]);

    expect($updated->value)->toBe(30);
    expect($updated->is_active)->toBeFalse();
});

it('can delete a voucher', function () {
    $service = new VoucherService;
    $voucher = Voucher::factory()->create();

    $service->delete($voucher);

    assertDatabaseMissing('vouchers', ['id' => $voucher->id]);
});
