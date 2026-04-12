<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Webhook;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    Webhook::query()->delete();
    User::query()->delete();
});

it('can list webhooks', function () {
    $user = User::factory()->create();
    Webhook::factory()->forTenant(fake()->numberBetween(1, 10))->count(3)->create();

    actingAs($user)
        ->get('/api/webhooks/webhooks')
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'url',
                    'events',
                    'is_active',
                ],
            ],
        ]);
});

it('can create a webhook', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post('/api/webhooks/webhooks', [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['subscription.created', 'invoice.paid'],
        ])
        ->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Webhook created successfully',
        ]);
});

it('can update a webhook', function () {
    $user = User::factory()->create();
    $webhook = Webhook::factory()->create();

    actingAs($user)
        ->put("/api/webhooks/webhooks/{$webhook->id}", [
            'name' => 'Updated Webhook',
            'url' => 'https://example.com/updated',
        ])
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Webhook updated successfully',
        ]);
});

it('can delete a webhook', function () {
    $user = User::factory()->create();
    $webhook = Webhook::factory()->create();

    actingAs($user)
        ->delete("/api/webhooks/webhooks/{$webhook->id}")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Webhook deleted successfully',
        ]);

    expect(Webhook::find($webhook->id))->toBeNull();
});

it('can toggle webhook status', function () {
    $user = User::factory()->create();
    $webhook = Webhook::factory()->create(['is_active' => true]);

    actingAs($user)
        ->post("/api/webhooks/webhooks/{$webhook->id}/toggle")
        ->assertStatus(200);

    expect($webhook->fresh()->is_active)->toBeFalse();
});

it('can test a webhook', function () {
    $user = User::factory()->create();
    $webhook = Webhook::factory()->create();

    actingAs($user)
        ->post("/api/webhooks/webhooks/{$webhook->id}/test")
        ->assertStatus(200);
});

it('can regenerate webhook secret', function () {
    $user = User::factory()->create();
    $webhook = Webhook::factory()->create();
    $oldSecret = $webhook->secret;

    actingAs($user)
        ->post("/api/webhooks/webhooks/{$webhook->id}/regenerate-secret")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Webhook secret regenerated successfully',
        ]);

    expect($webhook->fresh()->secret)->not->toBe($oldSecret);
});

it('requires authentication to access webhook endpoints', function () {
    get('/api/webhooks/webhooks')
        ->assertStatus(401);

    post('/api/webhooks/webhooks', [])
        ->assertStatus(401);
});
