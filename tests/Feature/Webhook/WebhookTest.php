<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Bootstrappers\DatabaseSessionBootstrapper;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Stancl\Tenancy\Jobs\DeleteDatabase;
use Stancl\Tenancy\Jobs\MigrateDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    config(['tenancy.bootstrappers' => [
        DatabaseTenancyBootstrapper::class,
        FilesystemTenancyBootstrapper::class,
        QueueTenancyBootstrapper::class,
        DatabaseSessionBootstrapper::class,
    ]]);

    Event::fake();

    Tenant::query()->delete();
    User::query()->delete();

    $tenant = Tenant::factory()->make();
    $tenant->id = (string) random_int(1, 999999);
    $tenant->save();

    Event::swap(new Dispatcher);

    CreateDatabase::dispatchSync($tenant);
    MigrateDatabase::dispatchSync($tenant);

    Webhook::query()->delete();

    tenancy()->initialize($tenant);
});

afterEach(function () {
    $tenant = tenancy()->tenant;
    if ($tenant) {
        try {
            DeleteDatabase::dispatchSync($tenant);
        } catch (Exception $e) {
        }
    }
});

it('can list webhooks', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    Webhook::factory()->for($tenant)->count(3)->create();

    actingAs($user)
        ->get("/api/{$tenant->id}/webhooks/webhooks")
        ->assertSuccessful()
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
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    actingAs($user)
        ->post("/api/{$tenant->id}/webhooks/webhooks", [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['subscription.created', 'invoice.paid'],
        ])
        ->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Webhook created successfully',
        ]);
});

it('can update a webhook', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $webhook = Webhook::factory()->for($tenant)->create();

    actingAs($user)
        ->put("/api/{$tenant->id}/webhooks/webhooks/{$webhook->id}", [
            'name' => 'Updated Webhook',
            'url' => 'https://example.com/updated',
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Webhook updated successfully',
        ]);
});

it('can delete a webhook', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $webhook = Webhook::factory()->for($tenant)->create();

    actingAs($user)
        ->delete("/api/{$tenant->id}/webhooks/webhooks/{$webhook->id}")
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Webhook deleted successfully',
        ]);

    expect(Webhook::find($webhook->id))->toBeNull();
});

it('can toggle webhook status', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $webhook = Webhook::factory()->for($tenant)->create(['is_active' => true]);

    actingAs($user)
        ->post("/api/{$tenant->id}/webhooks/webhooks/{$webhook->id}/toggle")
        ->assertSuccessful();

    expect($webhook->fresh()->is_active)->toBeFalse();
});

it('can test a webhook', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $webhook = Webhook::factory()->for($tenant)->create();

    actingAs($user)
        ->post("/api/{$tenant->id}/webhooks/webhooks/{$webhook->id}/test")
        ->assertSuccessful();
});

it('can regenerate webhook secret', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $webhook = Webhook::factory()->for($tenant)->create();
    $oldSecret = $webhook->secret;

    actingAs($user)
        ->post("/api/{$tenant->id}/webhooks/webhooks/{$webhook->id}/regenerate-secret")
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Webhook secret regenerated successfully',
        ]);

    expect($webhook->fresh()->secret)->not->toBe($oldSecret);
});

it('requires authentication to access webhook endpoints', function () {
    $tenant = tenancy()->tenant;

    get("/api/{$tenant->id}/webhooks/webhooks")
        ->assertUnauthorized();

    post("/api/{$tenant->id}/webhooks/webhooks", [])
        ->assertUnauthorized();
});
