<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    Subscription::query()->delete();
    Plan::query()->delete();
    User::query()->delete();
});

it('can create a subscription', function () {
    actingAs(User::factory()->create())
        ->post('/api/billing/subscriptions', [
            'plan_id' => Plan::factory()->create()->id,
            'payment_method' => 'card',
            'billing_cycle' => 'monthly',
        ])
        ->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'status',
                'billing_cycle',
                'plan' => [
                    'id',
                    'name',
                ],
            ],
        ]);
});

it('can get current subscription', function () {
    $user = User::factory()->create();
    actingAs($user)
        ->get('/api/billing/subscriptions')
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'status',
                'current_period_start',
                'current_period_end',
            ],
        ]);
});

it('can upgrade subscription', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->for($user)->active()->create();
    $newPlan = Plan::factory()->create(['price_monthly' => 99.99]);

    actingAs($user)
        ->post("/api/billing/subscriptions/{$subscription->id}/upgrade", [
            'plan_id' => $newPlan->id,
        ])
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription upgraded successfully',
        ]);
});

it('can cancel subscription', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->for($user)->active()->create();

    actingAs($user)
        ->post("/api/billing/subscriptions/{$subscription->id}/cancel")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
        ]);
});

it('can pause subscription', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->for($user)->active()->create();

    actingAs($user)
        ->post("/api/billing/subscriptions/{$subscription->id}/pause")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription paused successfully',
        ]);
});

it('can resume paused subscription', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->for($user)->create(['status' => 'paused']);

    actingAs($user)
        ->post("/api/billing/subscriptions/{$subscription->id}/resume")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription resumed successfully',
        ]);
});

it('requires authentication to access subscription endpoints', function () {
    get('/api/billing/subscriptions')
        ->assertStatus(401);

    post('/api/billing/subscriptions', [])
        ->assertStatus(401);
});
