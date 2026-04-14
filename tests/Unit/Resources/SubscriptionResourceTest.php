<?php

declare(strict_types=1);

use App\Http\Resources\Billing\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;

beforeEach(function () {
    // Create test data without database
    $this->plan = new Plan([
        'id' => 1,
        'name' => 'Pro Plan',
        'slug' => 'pro',
        'description' => 'Professional plan',
        'price_monthly' => 29.99,
        'price_yearly' => 299.99,
        'features' => ['Feature 1', 'Feature 2'],
        'max_users' => 10,
        'max_storage_mb' => 10240,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->user = new User([
        'id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
});

it('transforms subscription to array correctly', function () {
    $subscription = new Subscription;
    $subscription->id = 1;
    $subscription->tenant_id = 1;
    $subscription->user_id = 1;
    $subscription->plan_id = 1;
    $subscription->status = 'active';
    $subscription->starts_at = now()->subMonth();
    $subscription->ends_at = now()->addMonth();
    $subscription->trial_ends_at = null;
    $subscription->cancelled_at = null;
    $subscription->grace_period_ends_at = null;
    $subscription->metadata = ['key' => 'value'];
    $subscription->created_at = now()->subMonth();
    $subscription->updated_at = now();

    // Set relationships
    $subscription->setRelation('plan', $this->plan);
    $subscription->setRelation('user', $this->user);

    $resource = new SubscriptionResource($subscription);
    $request = Request::create('/');
    $result = $resource->toArray($request);

    expect($result)
        ->toBeArray()
        ->and($result['id'])->toBe(1)
        ->and($result['status'])->toBe('active')
        ->and($result['current_period_start'])->toBeString()
        ->and($result['current_period_end'])->toBeString()
        ->and($result['trial_ends_at'])->toBeNull()
        ->and($result['cancelled_at'])->toBeNull()
        ->and($result['grace_period_ends_at'])->toBeNull()
        ->and($result['cancellation_reason'])->toBeNull()
        ->and($result['metadata'])->toBe(['key' => 'value'])
        ->and($result['is_active'])->toBeBool()
        ->and($result['is_trialing'])->toBeBool()
        ->and($result['is_paused'])->toBeBool()
        ->and($result['is_cancelled'])->toBeBool()
        ->and($result['can_pause'])->toBeBool()
        ->and($result['can_cancel'])->toBeBool()
        ->and($result['can_upgrade'])->toBeBool()
        ->and($result['can_downgrade'])->toBeBool()
        ->and($result['days_remaining'])->toBeInt()
        ->and($result['plan'])->toBeArray()
        ->and($result['user'])->toBeArray()
        ->and($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});

it('includes is_active computed attribute correctly', function () {
    // Active subscription with future end date
    $activeSubscription = new Subscription([
        'id' => 1,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($activeSubscription);
    $request = Request::create('/');
    $result = $resource->toArray($request);

    expect($result['is_active'])->toBeTrue();

    // Active subscription with past end date
    $expiredSubscription = new Subscription([
        'id' => 2,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
    ]);

    $resource = new SubscriptionResource($expiredSubscription);
    $result = $resource->toArray($request);

    expect($result['is_active'])->toBeFalse();
});

it('includes is_trialing computed attribute correctly', function () {
    $request = Request::create('/');

    // Subscription with trialing status
    $trialingSubscription = new Subscription([
        'id' => 1,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'trialing',
        'starts_at' => now()->subWeek(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($trialingSubscription);
    $result = $resource->toArray($request);

    expect($result['is_trialing'])->toBeTrue();

    // Subscription with future trial_ends_at
    $futureTrialSubscription = new Subscription([
        'id' => 2,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subWeek(),
        'ends_at' => now()->addMonth(),
        'trial_ends_at' => now()->addWeek(),
    ]);

    $resource = new SubscriptionResource($futureTrialSubscription);
    $result = $resource->toArray($request);

    expect($result['is_trialing'])->toBeTrue();

    // Subscription with past trial_ends_at
    $pastTrialSubscription = new Subscription([
        'id' => 3,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subWeek(),
        'ends_at' => now()->addMonth(),
        'trial_ends_at' => now()->subDay(),
    ]);

    $resource = new SubscriptionResource($pastTrialSubscription);
    $result = $resource->toArray($request);

    expect($result['is_trialing'])->toBeFalse();
});

it('includes is_paused computed attribute correctly', function () {
    $request = Request::create('/');

    $pausedSubscription = new Subscription([
        'id' => 1,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'paused',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($pausedSubscription);
    $result = $resource->toArray($request);

    expect($result['is_paused'])->toBeTrue();

    $activeSubscription = new Subscription([
        'id' => 2,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($activeSubscription);
    $result = $resource->toArray($request);

    expect($result['is_paused'])->toBeFalse();
});

it('includes is_cancelled computed attribute correctly', function () {
    $request = Request::create('/');

    // Subscription with cancelled status
    $cancelledSubscription = new Subscription([
        'id' => 1,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'cancelled',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($cancelledSubscription);
    $result = $resource->toArray($request);

    expect($result['is_cancelled'])->toBeTrue();

    // Subscription with cancelled_at set
    $activeWithCancelSubscription = new Subscription([
        'id' => 2,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
        'cancelled_at' => now(),
    ]);

    $resource = new SubscriptionResource($activeWithCancelSubscription);
    $result = $resource->toArray($request);

    expect($result['is_cancelled'])->toBeTrue();

    // Active subscription without cancelled_at
    $activeSubscription = new Subscription([
        'id' => 3,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
        'cancelled_at' => null,
    ]);

    $resource = new SubscriptionResource($activeSubscription);
    $result = $resource->toArray($request);

    expect($result['is_cancelled'])->toBeFalse();
});

it('includes can_pause capability flag correctly', function () {
    $request = Request::create('/');

    // Active subscription
    $activeSubscription = new Subscription([
        'id' => 1,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($activeSubscription);
    $result = $resource->toArray($request);

    expect($result['can_pause'])->toBeTrue();

    // Trialing subscription
    $trialingSubscription = new Subscription([
        'id' => 2,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'trialing',
        'starts_at' => now()->subWeek(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($trialingSubscription);
    $result = $resource->toArray($request);

    expect($result['can_pause'])->toBeTrue();

    // Cancelled subscription
    $cancelledSubscription = new Subscription([
        'id' => 3,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'cancelled',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($cancelledSubscription);
    $result = $resource->toArray($request);

    expect($result['can_pause'])->toBeFalse();
});

it('includes can_cancel capability flag correctly', function () {
    $request = Request::create('/');

    // Active subscription
    $activeSubscription = new Subscription([
        'id' => 1,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($activeSubscription);
    $result = $resource->toArray($request);

    expect($result['can_cancel'])->toBeTrue();

    // Paused subscription
    $pausedSubscription = new Subscription([
        'id' => 2,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'paused',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($pausedSubscription);
    $result = $resource->toArray($request);

    expect($result['can_cancel'])->toBeTrue();

    // Cancelled subscription
    $cancelledSubscription = new Subscription([
        'id' => 3,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'cancelled',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($cancelledSubscription);
    $result = $resource->toArray($request);

    expect($result['can_cancel'])->toBeFalse();
});

it('includes can_upgrade capability flag correctly', function () {
    $request = Request::create('/');

    // Active subscription
    $activeSubscription = new Subscription([
        'id' => 1,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($activeSubscription);
    $result = $resource->toArray($request);

    expect($result['can_upgrade'])->toBeTrue();

    // Paused subscription
    $pausedSubscription = new Subscription([
        'id' => 2,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'paused',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($pausedSubscription);
    $result = $resource->toArray($request);

    expect($result['can_upgrade'])->toBeFalse();
});

it('includes can_downgrade capability flag correctly', function () {
    $request = Request::create('/');

    // Active subscription
    $activeSubscription = new Subscription([
        'id' => 1,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($activeSubscription);
    $result = $resource->toArray($request);

    expect($result['can_downgrade'])->toBeTrue();

    // Trialing subscription
    $trialingSubscription = new Subscription([
        'id' => 2,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'trialing',
        'starts_at' => now()->subWeek(),
        'ends_at' => now()->addMonth(),
    ]);

    $resource = new SubscriptionResource($trialingSubscription);
    $result = $resource->toArray($request);

    expect($result['can_downgrade'])->toBeFalse();
});

it('includes days_remaining computed attribute correctly', function () {
    $request = Request::create('/');

    // Subscription ending in 10 days
    $futureSubscription = new Subscription([
        'id' => 1,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addDays(10),
    ]);

    $resource = new SubscriptionResource($futureSubscription);
    $result = $resource->toArray($request);

    expect($result['days_remaining'])->toBeGreaterThanOrEqual(9)
        ->and($result['days_remaining'])->toBeLessThanOrEqual(10);

    // Subscription ended in the past
    $pastSubscription = new Subscription([
        'id' => 2,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDays(5),
    ]);

    $resource = new SubscriptionResource($pastSubscription);
    $result = $resource->toArray($request);

    expect($result['days_remaining'])->toBe(0);

    // Subscription with no end date
    $noEndDateSubscription = new Subscription([
        'id' => 3,
        'tenant_id' => 1,
        'user_id' => 1,
        'plan_id' => 1,
        'status' => 'active',
        'starts_at' => now()->subMonth(),
        'ends_at' => null,
    ]);

    $resource = new SubscriptionResource($noEndDateSubscription);
    $result = $resource->toArray($request);

    expect($result['days_remaining'])->toBe(0);
});

it('excludes plan and user when not loaded', function () {
    $subscription = new Subscription;
    $subscription->id = 1;
    $subscription->tenant_id = 1;
    $subscription->user_id = 1;
    $subscription->plan_id = 1;
    $subscription->status = 'active';
    $subscription->starts_at = now()->subMonth();
    $subscription->ends_at = now()->addMonth();

    // Don't set relationships
    $resource = new SubscriptionResource($subscription);
    $request = Request::create('/');
    $result = $resource->toArray($request);

    // When relationships are not loaded, they should be MissingValue objects
    expect($result['plan'])->toBeInstanceOf(MissingValue::class)
        ->and($result['user'])->toBeInstanceOf(MissingValue::class);
});

it('includes plan and user when loaded', function () {
    $subscription = new Subscription;
    $subscription->id = 1;
    $subscription->tenant_id = 1;
    $subscription->user_id = 1;
    $subscription->plan_id = 1;
    $subscription->status = 'active';
    $subscription->starts_at = now()->subMonth();
    $subscription->ends_at = now()->addMonth();

    // Set relationships with proper IDs
    $plan = new Plan;
    $plan->id = 1;
    $plan->name = 'Pro Plan';
    $plan->slug = 'pro';
    $plan->description = 'Professional plan';
    $plan->price_monthly = 29.99;
    $plan->price_yearly = 299.99;
    $plan->features = ['Feature 1', 'Feature 2'];
    $plan->max_users = 10;
    $plan->max_storage_mb = 10240;
    $plan->is_active = true;
    $plan->sort_order = 1;

    $user = new User;
    $user->id = 1;
    $user->name = 'Test User';
    $user->email = 'test@example.com';

    $subscription->setRelation('plan', $plan);
    $subscription->setRelation('user', $user);

    $resource = new SubscriptionResource($subscription);
    $request = Request::create('/');
    $result = $resource->toArray($request);

    expect($result['plan'])->toBeArray()
        ->and($result['plan']['id'])->toBe(1)
        ->and($result['plan']['name'])->toBe('Pro Plan')
        ->and($result['user'])->toBeArray()
        ->and($result['user']['id'])->toBe(1)
        ->and($result['user']['name'])->toBe('Test User');
});
