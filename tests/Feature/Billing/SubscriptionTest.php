<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
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

// Disable RefreshDatabase for tenant tests as it conflicts with tenancy
// We'll manage database state manually
beforeEach(function () {
    // Disable cache bootstrapper for tests to avoid array cache driver issues
    config(['tenancy.bootstrappers' => [
        DatabaseTenancyBootstrapper::class,
        FilesystemTenancyBootstrapper::class,
        QueueTenancyBootstrapper::class,
        DatabaseSessionBootstrapper::class,
    ]]);

    // Fake events to prevent tenant creation from triggering seeding
    Event::fake();

    Subscription::query()->delete();
    Plan::query()->delete();
    Tenant::query()->delete();
    User::query()->delete();

    // Create a tenant for testing with explicit ID (integer for now to match subscriptions.tenant_id)
    $tenant = Tenant::factory()->active()->make();
    $tenant->id = (string) random_int(1, 999999);
    $tenant->save();

    // Re-enable events for the rest of the test
    Event::swap(new Dispatcher);

    // Manually create and migrate tenant database
    CreateDatabase::dispatchSync($tenant);
    MigrateDatabase::dispatchSync($tenant);

    // Manually initialize tenancy
    tenancy()->initialize($tenant);
});

afterEach(function () {
    // Clean up tenant database
    $tenant = tenancy()->tenant;
    if ($tenant) {
        try {
            DeleteDatabase::dispatchSync($tenant);
        } catch (Exception $e) {
            // Ignore errors during cleanup
        }
    }
});

it('can create a subscription', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $response = actingAs($user)
        ->post("/api/{$tenant->id}/billing/subscriptions", [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
        ]);

    // Debug: check what the actual response is
    if ($response->status() === 404) {
        dump('404 Response:', $response->json());
    }

    $response
        ->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'status',
                'plan' => [
                    'id',
                    'name',
                ],
            ],
        ]);
});

it('can get current subscription', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // Create an active subscription
    Subscription::factory()
        ->for($tenant)
        ->for($user)
        ->for($plan)
        ->active()
        ->create();

    actingAs($user)
        ->get("/api/{$tenant->id}/billing/subscriptions")
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

it('can list all subscriptions', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    // Create multiple subscriptions
    Subscription::factory()
        ->for($tenant)
        ->for($user)
        ->for($plan)
        ->count(3)
        ->create();

    actingAs($user)
        ->get("/api/{$tenant->id}/billing/subscriptions")
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'status',
                        'plan' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ],
        ]);
});

it('can upgrade subscription', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $newPlan = Plan::factory()->create(['price_monthly' => 99.99]);

    $subscription = Subscription::factory()
        ->for($tenant)
        ->for($user)
        ->for($plan)
        ->active()
        ->create();

    actingAs($user)
        ->post("/api/{$tenant->id}/billing/subscriptions/{$subscription->id}/upgrade", [
            'plan_id' => $newPlan->id,
        ])
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription upgraded successfully',
        ]);
});

it('can cancel subscription', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()
        ->for($tenant)
        ->for($user)
        ->for($plan)
        ->active()
        ->create();

    actingAs($user)
        ->post("/api/{$tenant->id}/billing/subscriptions/{$subscription->id}/cancel")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
        ]);
});

it('can cancel subscription with reason', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()
        ->for($tenant)
        ->for($user)
        ->for($plan)
        ->active()
        ->create();

    actingAs($user)
        ->post("/api/{$tenant->id}/billing/subscriptions/{$subscription->id}/cancel", [
            'reason' => 'Too expensive',
            'cancellation_feedback' => 'Found a cheaper alternative',
        ])
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
        ]);
});

it('can pause subscription', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()
        ->for($tenant)
        ->for($user)
        ->for($plan)
        ->active()
        ->create();

    actingAs($user)
        ->post("/api/{$tenant->id}/billing/subscriptions/{$subscription->id}/pause")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription paused successfully',
        ]);
});

it('can resume paused subscription', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()
        ->for($tenant)
        ->for($user)
        ->for($plan)
        ->create(['status' => 'paused']);

    actingAs($user)
        ->post("/api/{$tenant->id}/billing/subscriptions/{$subscription->id}/resume")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription resumed successfully',
        ]);
});

it('requires authentication to access subscription endpoints', function () {
    $tenant = tenancy()->tenant;

    get("/api/{$tenant->id}/billing/subscriptions")
        ->assertStatus(401);

    post("/api/{$tenant->id}/billing/subscriptions", [])
        ->assertStatus(401);
});

it('can downgrade subscription', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price_monthly' => 99.99]);
    $newPlan = Plan::factory()->create(['price_monthly' => 19.99]);

    $subscription = Subscription::factory()
        ->for($tenant)
        ->for($user)
        ->for($plan)
        ->active()
        ->create();

    actingAs($user)
        ->post("/api/{$tenant->id}/billing/subscriptions/{$subscription->id}/downgrade", [
            'plan_id' => $newPlan->id,
        ])
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription downgrade scheduled',
        ]);
});

it('can renew subscription', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()
        ->for($tenant)
        ->for($user)
        ->for($plan)
        ->active()
        ->create();

    actingAs($user)
        ->post("/api/{$tenant->id}/billing/subscriptions/{$subscription->id}/renew")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription renewed successfully',
        ]);
});

it('can apply voucher to subscription', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $voucher = Voucher::factory()->create(['is_active' => true]);

    $subscription = Subscription::factory()
        ->for($tenant)
        ->for($user)
        ->for($plan)
        ->active()
        ->create();

    actingAs($user)
        ->post("/api/{$tenant->id}/billing/subscriptions/{$subscription->id}/apply-voucher", [
            'voucher_id' => $voucher->id,
        ])
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Voucher applied successfully',
        ]);
});
