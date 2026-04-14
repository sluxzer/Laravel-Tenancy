<?php

declare(strict_types=1);

use App\Exceptions\SubscriptionException;
use App\Exceptions\VoucherException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Repositories\Contracts\VoucherRepositoryInterface;
use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear tables
    Subscription::query()->delete();
    Plan::query()->delete();
    User::query()->delete();
    Voucher::query()->delete();
});

it('can create a new subscription', function () {
    $plan = Plan::factory()->make(['id' => 1, 'is_active' => true, 'price_monthly' => 29.99]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $subscription = Subscription::factory()->make([
        'id' => 1,
        'plan_id' => 1,
        'status' => 'active',
    ]);

    $subscriptionRepo->shouldReceive('getActiveForTenant')
        ->once()
        ->andReturn(new Collection);

    $subscriptionRepo->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) {
            return $data['tenant_id'] === 1 &&
                $data['plan_id'] === 1 &&
                $data['status'] === 'active' &&
                $data['metadata']['billing_cycle'] === 'monthly' &&
                $data['metadata']['custom_field'] === 'value' &&
                isset($data['starts_at']) &&
                isset($data['ends_at']);
        }))
        ->andReturn($subscription);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $tenant = new Tenant(['id' => 1, 'name' => 'Test Tenant']);

    $result = $service->create($tenant, $plan, 'monthly', null, ['custom_field' => 'value']);

    expect($result)->toBeInstanceOf(Subscription::class);
});

it('throws exception when creating subscription for tenant with active subscription', function () {
    $plan = Plan::factory()->make(['id' => 1, 'is_active' => true]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $activeSubscription = Subscription::factory()->make(['id' => 1, 'status' => 'active']);

    $collection = new Collection;
    $collection->add($activeSubscription);

    $subscriptionRepo->shouldReceive('getActiveForTenant')
        ->once()
        ->andReturn($collection);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $tenant = new Tenant(['id' => 1, 'name' => 'Test Tenant']);

    $service->create($tenant, $plan, 'monthly');
})->throws(SubscriptionException::class, 'User already has an active subscription');

it('can create a subscription with yearly billing cycle', function () {
    $plan = Plan::factory()->make(['id' => 1, 'is_active' => true, 'price_monthly' => 29.99]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $subscription = Subscription::factory()->make([
        'id' => 1,
        'plan_id' => 1,
        'status' => 'active',
    ]);

    $subscriptionRepo->shouldReceive('getActiveForTenant')
        ->once()
        ->andReturn(new Collection);

    $subscriptionRepo->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) {
            return $data['tenant_id'] === 1 &&
                $data['plan_id'] === 1 &&
                $data['status'] === 'active' &&
                $data['metadata']['billing_cycle'] === 'yearly' &&
                isset($data['starts_at']) &&
                isset($data['ends_at']);
        }))
        ->andReturn($subscription);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $tenant = new Tenant(['id' => 1, 'name' => 'Test Tenant']);

    $result = $service->create($tenant, $plan, 'yearly');

    expect($result)->toBeInstanceOf(Subscription::class);
});

it('throws exception when creating subscription with inactive plan', function () {
    $plan = Plan::factory()->make(['id' => 1, 'is_active' => false]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $subscriptionRepo->shouldReceive('getActiveForTenant')
        ->once()
        ->andReturn(new Collection);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $tenant = new Tenant(['id' => 1, 'name' => 'Test Tenant']);

    $service->create($tenant, $plan, 'monthly');
})->throws(SubscriptionException::class, 'Selected plan is not available');

it('can upgrade a subscription to higher plan', function () {
    $currentPlan = Plan::factory()->make(['id' => 1, 'price_monthly' => 29.99, 'is_active' => true]);
    $newPlan = Plan::factory()->make(['id' => 2, 'price_monthly' => 49.99, 'is_active' => true]);

    $subscription = Subscription::factory()->make([
        'id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'metadata' => [],
    ]);

    // Mock the plan relationship
    $subscription->setRelation('plan', $currentPlan);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $subscriptionRepo->shouldReceive('update')
        ->once()
        ->with(
            Mockery::type(Subscription::class),
            Mockery::on(function ($data) {
                return $data['plan_id'] === 2 &&
                    isset($data['metadata']) &&
                    isset($data['metadata']['previous_plan_id']) &&
                    $data['metadata']['previous_plan_id'] === 1 &&
                    isset($data['metadata']['upgrade_date']);
            })
        )
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $result = $service->upgrade($subscription, $newPlan);

    expect($result)->toBeInstanceOf(Subscription::class);
    expect($result->plan_id)->toBe(2);
});

it('throws exception when upgrading to lower-priced plan', function () {
    $currentPlan = Plan::factory()->make(['id' => 1, 'price_monthly' => 49.99, 'is_active' => true]);
    $newPlan = Plan::factory()->make(['id' => 2, 'price_monthly' => 29.99, 'is_active' => true]);

    $subscription = Subscription::factory()->make([
        'id' => 1,
        'plan_id' => 1,
        'status' => 'active',
    ]);

    // Mock the plan relationship
    $subscription->setRelation('plan', $currentPlan);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $service->upgrade($subscription, $newPlan);
})->throws(SubscriptionException::class, 'Cannot upgrade to a lower-priced plan');

it('throws exception when upgrading cancelled subscription', function () {
    $currentPlan = Plan::factory()->make(['id' => 1, 'price_monthly' => 29.99, 'is_active' => true]);
    $newPlan = Plan::factory()->make(['id' => 2, 'price_monthly' => 49.99, 'is_active' => true]);

    $subscription = Subscription::factory()->make([
        'id' => 1,
        'plan_id' => 1,
        'status' => 'cancelled',
    ]);

    // Mock the plan relationship
    $subscription->setRelation('plan', $currentPlan);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $service->upgrade($subscription, $newPlan);
})->throws(SubscriptionException::class, 'Invalid subscription status');

it('can downgrade a subscription to lower plan', function () {
    $currentPlan = Plan::factory()->make(['id' => 1, 'price_monthly' => 49.99, 'is_active' => true]);
    $newPlan = Plan::factory()->make(['id' => 2, 'price_monthly' => 29.99, 'is_active' => true]);

    $subscription = Subscription::factory()->make([
        'id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'ends_at' => now()->addMonth(),
        'metadata' => [],
    ]);

    // Mock the plan relationship
    $subscription->setRelation('plan', $currentPlan);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $subscriptionRepo->shouldReceive('update')
        ->once()
        ->with(
            Mockery::type(Subscription::class),
            Mockery::on(function ($data) {
                return isset($data['metadata']) &&
                    isset($data['metadata']['previous_plan_id']) &&
                    $data['metadata']['previous_plan_id'] === 1 &&
                    isset($data['metadata']['pending_plan_id']) &&
                    $data['metadata']['pending_plan_id'] === 2 &&
                    isset($data['metadata']['downgrade_date']) &&
                    isset($data['metadata']['downgrade_effective_at']);
            })
        )
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $result = $service->downgrade($subscription, $newPlan);

    expect($result)->toBeInstanceOf(Subscription::class);
    expect($result->metadata['pending_plan_id'])->toBe(2);
});

it('throws exception when downgrading to higher-priced plan', function () {
    $currentPlan = Plan::factory()->make(['id' => 1, 'price_monthly' => 29.99, 'is_active' => true]);
    $newPlan = Plan::factory()->make(['id' => 2, 'price_monthly' => 49.99, 'is_active' => true]);

    $subscription = Subscription::factory()->make([
        'id' => 1,
        'plan_id' => 1,
        'status' => 'active',
    ]);

    // Mock the plan relationship
    $subscription->setRelation('plan', $currentPlan);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $service->downgrade($subscription, $newPlan);
})->throws(SubscriptionException::class, 'Cannot downgrade to a higher-priced plan');

it('can pause an active subscription', function () {
    $subscription = Subscription::factory()->make([
        'id' => 1,
        'status' => 'active',
    ]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $subscriptionRepo->shouldReceive('update')
        ->once()
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $result = $service->pause($subscription);

    expect($result)->toBeInstanceOf(Subscription::class);
});

it('throws exception when pausing non-active subscription', function () {
    $subscription = Subscription::factory()->make([
        'id' => 1,
        'status' => 'cancelled',
    ]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $service->pause($subscription);
})->throws(SubscriptionException::class, 'Only active subscriptions can be paused');

it('can resume a paused subscription', function () {
    $subscription = Subscription::factory()->make([
        'id' => 1,
        'status' => 'paused',
        'paused_at' => now(),
    ]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $subscriptionRepo->shouldReceive('update')
        ->once()
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $result = $service->resume($subscription);

    expect($result)->toBeInstanceOf(Subscription::class);
});

it('throws exception when resuming non-paused subscription', function () {
    $subscription = Subscription::factory()->make([
        'id' => 1,
        'status' => 'active',
    ]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $service->resume($subscription);
})->throws(SubscriptionException::class, 'Only paused subscriptions can be resumed');

it('can cancel a subscription', function () {
    $subscription = Subscription::factory()->make([
        'id' => 1,
        'status' => 'active',
        'metadata' => [],
    ]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $subscriptionRepo->shouldReceive('update')
        ->once()
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $result = $service->cancel($subscription, 'Too expensive');

    expect($result)->toBeInstanceOf(Subscription::class);
});

it('can cancel a subscription without reason', function () {
    $subscription = Subscription::factory()->make([
        'id' => 1,
        'status' => 'active',
        'metadata' => [],
    ]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $subscriptionRepo->shouldReceive('update')
        ->once()
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $result = $service->cancel($subscription);

    expect($result)->toBeInstanceOf(Subscription::class);
});

it('throws exception when cancelling already cancelled subscription', function () {
    $subscription = Subscription::factory()->make([
        'id' => 1,
        'status' => 'cancelled',
    ]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $service->cancel($subscription);
})->throws(SubscriptionException::class, 'Subscription is cancelled and cannot be cancelled');

it('can renew an expiring subscription', function () {
    $subscription = Subscription::factory()->make([
        'id' => 1,
        'status' => 'active',
        'ends_at' => now()->addDays(3),
    ]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $subscriptionRepo->shouldReceive('update')
        ->once()
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $result = $service->renew($subscription);

    expect($result)->toBeInstanceOf(Subscription::class);
});

it('can renew an expired subscription', function () {
    $subscription = Subscription::factory()->make([
        'id' => 1,
        'status' => 'expired',
        'ends_at' => now()->subDays(5),
    ]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $subscriptionRepo->shouldReceive('update')
        ->once()
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $result = $service->renew($subscription);

    expect($result)->toBeInstanceOf(Subscription::class);
});

it('throws exception when renewing subscription not due for renewal', function () {
    $subscription = Subscription::factory()->make([
        'id' => 1,
        'status' => 'active',
        'ends_at' => now()->addMonth(),
    ]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $service->renew($subscription);
})->throws(SubscriptionException::class, 'Cannot renew this subscription');

it('can apply voucher to subscription', function () {
    $plan = Plan::factory()->make(['id' => 1]);
    $user = User::factory()->make(['id' => 1]);
    $voucher = Voucher::factory()->make([
        'id' => 1,
        'code' => 'SAVE20',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'max_uses' => 100,
        'expires_at' => now()->addMonth(),
        'is_active' => true,
        'uses_count' => 0,
        'plan_id' => 1,
    ]);

    $subscription = Subscription::factory()->make([
        'id' => 1,
        'plan_id' => 1,
        'user_id' => 1,
        'status' => 'active',
        'metadata' => [],
    ]);

    // Mock the relationships
    $subscription->setRelation('plan', $plan);
    $subscription->setRelation('user', $user);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $voucherRepo->shouldReceive('canUseVoucher')
        ->once()
        ->with($voucher, $user, $plan)
        ->andReturn(true);

    $subscriptionRepo->shouldReceive('update')
        ->once()
        ->andReturn(true);

    $voucherRepo->shouldReceive('incrementUsage')
        ->once()
        ->with(1)
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $service->applyVoucher($subscription, $voucher);

    // If we get here, no exception was thrown
    expect(true)->toBeTrue();
});

it('throws exception when applying expired voucher', function () {
    $plan = Plan::factory()->make(['id' => 1]);
    $user = User::factory()->make(['id' => 1]);
    $voucher = Voucher::factory()->make([
        'id' => 1,
        'code' => 'EXPIRED',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'expires_at' => now()->subDay(),
        'is_active' => true,
        'plan_id' => 1,
    ]);

    $subscription = Subscription::factory()->make([
        'id' => 1,
        'plan_id' => 1,
        'user_id' => 1,
        'status' => 'active',
    ]);

    // Mock the relationships
    $subscription->setRelation('plan', $plan);
    $subscription->setRelation('user', $user);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $voucherRepo->shouldReceive('canUseVoucher')
        ->once()
        ->with($voucher, $user, $plan)
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $service->applyVoucher($subscription, $voucher);
})->throws(VoucherException::class, 'Voucher has expired');

it('throws exception when applying voucher with max uses reached', function () {
    $plan = Plan::factory()->make(['id' => 1]);
    $user = User::factory()->make(['id' => 1]);
    $voucher = Voucher::factory()->make([
        'id' => 1,
        'code' => 'MAXED',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'max_uses' => 1,
        'uses_count' => 1,
        'is_active' => true,
        'plan_id' => 1,
    ]);

    $subscription = Subscription::factory()->make([
        'id' => 1,
        'plan_id' => 1,
        'user_id' => 1,
        'status' => 'active',
    ]);

    // Mock the relationships
    $subscription->setRelation('plan', $plan);
    $subscription->setRelation('user', $user);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $voucherRepo->shouldReceive('canUseVoucher')
        ->once()
        ->with($voucher, $user, $plan)
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $service->applyVoucher($subscription, $voucher);
})->throws(VoucherException::class, 'Voucher has reached maximum uses');

it('throws exception when applying voucher invalid for plan', function () {
    $plan = Plan::factory()->make(['id' => 1]);
    $otherPlan = Plan::factory()->make(['id' => 2]);
    $user = User::factory()->make(['id' => 1]);
    $voucher = Voucher::factory()->make([
        'id' => 1,
        'code' => 'PLANONLY',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'is_active' => true,
        'plan_id' => 2,
    ]);

    $subscription = Subscription::factory()->make([
        'id' => 1,
        'plan_id' => 1,
        'user_id' => 1,
        'status' => 'active',
    ]);

    // Mock the relationships
    $subscription->setRelation('plan', $plan);
    $subscription->setRelation('user', $user);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $voucherRepo->shouldReceive('canUseVoucher')
        ->once()
        ->with($voucher, $user, $plan)
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $service->applyVoucher($subscription, $voucher);
})->throws(VoucherException::class, 'This voucher is not valid for the selected plan');

it('throws exception when applying already used voucher', function () {
    $plan = Plan::factory()->make(['id' => 1]);
    $user = User::factory()->make(['id' => 1]);
    $voucher = Voucher::factory()->make([
        'id' => 1,
        'code' => 'ONCE',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'is_active' => true,
        'plan_id' => 1,
    ]);

    $subscription = Subscription::factory()->make([
        'id' => 1,
        'plan_id' => 1,
        'user_id' => 1,
        'status' => 'active',
        'metadata' => [
            'applied_vouchers' => [1],
        ],
    ]);

    // Mock the relationships
    $subscription->setRelation('plan', $plan);
    $subscription->setRelation('user', $user);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $planRepo = mock(PlanRepositoryInterface::class);
    $voucherRepo = mock(VoucherRepositoryInterface::class);

    $voucherRepo->shouldReceive('canUseVoucher')
        ->once()
        ->with($voucher, $user, $plan)
        ->andReturn(true);

    $service = new SubscriptionService($subscriptionRepo, $planRepo, $voucherRepo);

    $service->applyVoucher($subscription, $voucher);
})->throws(VoucherException::class, 'You have already used this voucher');
