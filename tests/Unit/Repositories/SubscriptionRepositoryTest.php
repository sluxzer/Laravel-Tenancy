<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Subscription;
use App\Repositories\Eloquent\SubscriptionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disable events to prevent tenancy bootstrapping during factory creation
    Event::fake();

    $this->tenantId = 1;
    $this->repository = new SubscriptionRepository(new Subscription);
});

it('can get active subscription for user', function () {
    $userId = 1;
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()
        ->create([
            'user_id' => $userId,
            'plan_id' => $plan->id,
            'tenant_id' => $this->tenantId,
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);

    $result = $this->repository->getActiveForUser($userId);

    expect($result)->toBeInstanceOf(Subscription::class)
        ->and($result->id)->toBe($subscription->id)
        ->and($result->plan->id)->toBe($plan->id);
});

it('returns null when user has no active subscription', function () {
    $userId = 1;

    $result = $this->repository->getActiveForUser($userId);

    expect($result)->toBeNull();
});

it('can get active subscriptions for tenant', function () {
    $plan = Plan::factory()->create();

    Subscription::factory()
        ->create([
            'user_id' => 1,
            'plan_id' => $plan->id,
            'tenant_id' => $this->tenantId,
            'status' => 'active',
        ]);

    Subscription::factory()
        ->create([
            'user_id' => 2,
            'plan_id' => $plan->id,
            'tenant_id' => $this->tenantId,
            'status' => 'cancelled',
        ]);

    $result = $this->repository->getActiveForTenant($this->tenantId);

    expect($result)->toHaveCount(1)
        ->and($result->first()->status)->toBe('active');
});

it('can get subscriptions by tenant with status filter', function () {
    $plan = Plan::factory()->create();

    Subscription::factory()
        ->create([
            'user_id' => 1,
            'plan_id' => $plan->id,
            'tenant_id' => $this->tenantId,
            'status' => 'active',
        ]);

    Subscription::factory()
        ->create([
            'user_id' => 2,
            'plan_id' => $plan->id,
            'tenant_id' => $this->tenantId,
            'status' => 'cancelled',
        ]);

    $result = $this->repository->getByTenant($this->tenantId, 'cancelled', 20);

    expect($result)->toHaveCount(1)
        ->and($result->first()->status)->toBe('cancelled');
});

it('can find subscription by tenant and id', function () {
    $plan = Plan::factory()->create();
    $otherTenantId = 999;

    $subscription = Subscription::factory()
        ->create([
            'user_id' => 1,
            'plan_id' => $plan->id,
            'tenant_id' => $this->tenantId,
        ]);

    Subscription::factory()
        ->create([
            'user_id' => 2,
            'plan_id' => $plan->id,
            'tenant_id' => $otherTenantId,
        ]);

    $result = $this->repository->findByTenant($this->tenantId, $subscription->id);

    expect($result)->toBeInstanceOf(Subscription::class)
        ->and($result->id)->toBe($subscription->id);
});

it('can count subscriptions for tenant', function () {
    Subscription::factory()
        ->count(3)
        ->create([
            'user_id' => fake()->numberBetween(1, 100),
            'plan_id' => Plan::factory()->sequence(
                ['name' => 'Plan 1', 'slug' => 'plan-1'],
                ['name' => 'Plan 2', 'slug' => 'plan-2'],
                ['name' => 'Plan 3', 'slug' => 'plan-3'],
            ),
            'tenant_id' => $this->tenantId,
            'status' => 'active',
        ]);

    Subscription::factory()
        ->count(2)
        ->create([
            'user_id' => fake()->numberBetween(1, 100),
            'plan_id' => Plan::factory()->sequence(
                ['name' => 'Plan 4', 'slug' => 'plan-4'],
                ['name' => 'Plan 5', 'slug' => 'plan-5'],
            ),
            'tenant_id' => $this->tenantId,
            'status' => 'cancelled',
        ]);

    $result = $this->repository->countForTenant($this->tenantId, 'active');

    expect($result)->toBe(3);
});
