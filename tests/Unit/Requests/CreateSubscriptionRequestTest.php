<?php

declare(strict_types=1);

use App\Http\Requests\Billing\CreateSubscriptionRequest;
use App\Models\Plan;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Create an authenticated user for authorization tests
    $this->user = User::factory()->create();
    $this->plan = Plan::factory()->active()->create();
});

it('authorizes authenticated users', function () {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST');

    actingAs($this->user);

    expect($request->authorize())->toBeTrue();
});

it('does not authorize unauthenticated users', function () {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST');

    expect($request->authorize())->toBeFalse();
});

it('validates with all required fields', function () {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
    ]);

    $request->setUserResolver(fn () => $this->user);

    $validator = validator(
        $request->all(),
        $request->rules(),
        $request->messages()
    );

    expect($validator->passes())->toBeTrue();
});

it('requires plan_id', function () {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'billing_cycle' => 'monthly',
    ]);

    $request->setUserResolver(fn () => $this->user);

    $validator = validator(
        $request->all(),
        $request->rules(),
        $request->messages()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('plan_id'))->toBeTrue();
    expect($validator->errors()->get('plan_id')[0])->toBe('The plan ID is required.');
});

it('requires billing_cycle', function () {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
    ]);

    $request->setUserResolver(fn () => $this->user);

    $validator = validator(
        $request->all(),
        $request->rules(),
        $request->messages()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('billing_cycle'))->toBeTrue();
    expect($validator->errors()->get('billing_cycle')[0])->toBe('The billing cycle is required.');
});

it('validates plan_id must be integer', function () {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => 'not-an-integer',
        'billing_cycle' => 'monthly',
    ]);

    $request->setUserResolver(fn () => $this->user);

    $validator = validator(
        $request->all(),
        $request->rules(),
        $request->messages()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('plan_id'))->toBeTrue();
    expect($validator->errors()->get('plan_id')[0])->toBe('The plan ID must be a valid integer.');
});

it('validates plan_id must exist and be active', function () {
    // Create an inactive plan
    $inactivePlan = Plan::factory()->inactive()->create();

    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $inactivePlan->id,
        'billing_cycle' => 'monthly',
    ]);

    $request->setUserResolver(fn () => $this->user);

    $validator = validator(
        $request->all(),
        $request->rules(),
        $request->messages()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('plan_id'))->toBeTrue();
    expect($validator->errors()->get('plan_id')[0])->toBe('The selected plan does not exist or is not active.');
});

it('validates billing_cycle must be valid value', function (string $invalidCycle) {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => $invalidCycle,
    ]);

    $request->setUserResolver(fn () => $this->user);

    $validator = validator(
        $request->all(),
        $request->rules(),
        $request->messages()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('billing_cycle'))->toBeTrue();
    expect($validator->errors()->get('billing_cycle')[0])->toBe('The billing cycle must be one of: monthly, yearly, or quarterly.');
})->with([
    'invalid',
    'daily',
    'weekly',
    'bi-annually',
]);

it('accepts valid billing cycles', function (string $validCycle) {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => $validCycle,
    ]);

    $request->setUserResolver(fn () => $this->user);

    $validator = validator(
        $request->all(),
        $request->rules(),
        $request->messages()
    );

    expect($validator->passes())->toBeTrue();
})->with([
    'monthly',
    'yearly',
    'quarterly',
]);

it('validates user_id must be integer if provided', function () {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'user_id' => 'not-an-integer',
    ]);

    $request->setUserResolver(fn () => $this->user);

    $validator = validator(
        $request->all(),
        $request->rules(),
        $request->messages()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('user_id'))->toBeTrue();
    expect($validator->errors()->get('user_id')[0])->toBe('The user ID must be a valid integer.');
});

it('validates user_id must exist if provided', function () {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'user_id' => 999999, // Non-existent user ID
    ]);

    $request->setUserResolver(fn () => $this->user);

    $validator = validator(
        $request->all(),
        $request->rules(),
        $request->messages()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('user_id'))->toBeTrue();
    expect($validator->errors()->get('user_id')[0])->toBe('The specified user does not exist.');
});

it('accepts valid user_id if provided', function () {
    $user = User::factory()->create();

    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'user_id' => $user->id,
    ]);

    $request->setUserResolver(fn () => $this->user);

    $validator = validator(
        $request->all(),
        $request->rules(),
        $request->messages()
    );

    expect($validator->passes())->toBeTrue();
});

it('validates metadata must be array if provided', function () {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'metadata' => 'not-an-array',
    ]);

    $request->setUserResolver(fn () => $this->user);

    $validator = validator(
        $request->all(),
        $request->rules(),
        $request->messages()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('metadata'))->toBeTrue();
    expect($validator->errors()->get('metadata')[0])->toBe('The metadata must be an array.');
});

it('accepts valid metadata if provided', function () {
    $metadata = [
        'promotion_code' => 'PROMO123',
        'source' => 'website',
    ];

    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'metadata' => $metadata,
    ]);

    $request->setUserResolver(fn () => $this->user);

    $validator = validator(
        $request->all(),
        $request->rules(),
        $request->messages()
    );

    expect($validator->passes())->toBeTrue();
});

it('returns plan model via getPlan helper', function () {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
    ]);

    $request->setUserResolver(fn () => $this->user);

    expect($request->getPlan()->id)->toBe($this->plan->id);
    expect($request->getPlan())->toBeInstanceOf(Plan::class);
});

it('returns user model via getSubscriptionUser helper when provided', function () {
    $user = User::factory()->create();

    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'user_id' => $user->id,
    ]);

    $request->setUserResolver(fn () => $this->user);

    expect($request->getSubscriptionUser()->id)->toBe($user->id);
    expect($request->getSubscriptionUser())->toBeInstanceOf(User::class);
});

it('returns null via getSubscriptionUser helper when not provided', function () {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
    ]);

    $request->setUserResolver(fn () => $this->user);

    expect($request->getSubscriptionUser())->toBeNull();
});

it('returns metadata via getMetadata helper when provided', function () {
    $metadata = [
        'promotion_code' => 'PROMO123',
        'source' => 'website',
    ];

    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'metadata' => $metadata,
    ]);

    $request->setUserResolver(fn () => $this->user);

    expect($request->getMetadata())->toBe($metadata);
});

it('returns empty array via getMetadata helper when not provided', function () {
    $request = CreateSubscriptionRequest::create('/api/subscriptions', 'POST', [
        'plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
    ]);

    $request->setUserResolver(fn () => $this->user);

    expect($request->getMetadata())->toBe([]);
});
