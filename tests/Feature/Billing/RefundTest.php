<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\Refund;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
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

    Refund::query()->delete();
    Transaction::query()->delete();
    Invoice::query()->delete();
    Subscription::query()->delete();
    User::query()->delete();
    Tenant::query()->delete();

    // Create a tenant for testing with explicit ID
    $tenantId = (string) random_int(1, 999999);
    $tenant = Tenant::withoutEvents(fn () => Tenant::create(array_merge(Tenant::factory()->raw(), ['id' => $tenantId])));

    // Re-enable events for rest of test
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

it('can list refunds', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    Refund::factory()
        ->for($tenant)
        ->for($user)
        ->count(3)
        ->create();

    actingAs($user)
        ->get('/api/{tenant}/billing/refunds')
        ->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'amount',
                    'currency',
                    'status',
                    'reason',
                ],
            ],
        ]);
});

it('can get a specific refund', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    $refund = Refund::factory()
        ->for($tenant)
        ->for($user)
        ->create();

    actingAs($user)
        ->get("/api/{tenant}/billing/refunds/{$refund->id}")
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $refund->id,
            ],
        ]);
});

it('can create a refund', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    $transaction = Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->payment()
        ->completed()
        ->create();

    $refundData = [
        'transaction_id' => $transaction->id,
        'amount' => 50.00,
        'reason' => 'requested_by_customer',
    ];

    actingAs($user)
        ->post('/api/{tenant}/billing/refunds', $refundData)
        ->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Refund created successfully',
        ]);
});

it('can filter refunds by status', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    Refund::factory()
        ->for($tenant)
        ->for($user)
        ->processed()
        ->count(2)
        ->create();

    Refund::factory()
        ->for($tenant)
        ->for($user)
        ->pending()
        ->count(1)
        ->create();

    actingAs($user)
        ->get('/api/{tenant}/billing/refunds?status=processed')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('can process a refund', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    $transaction = Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->payment()
        ->completed()
        ->create();

    $refund = Refund::factory()
        ->for($tenant)
        ->for($user)
        ->forTransaction($transaction->id)
        ->pending()
        ->create();

    actingAs($user)
        ->post("/api/{tenant}/billing/refunds/{$refund->id}/process")
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Refund processed successfully',
        ]);
});

it('can cancel a refund', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    $transaction = Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->payment()
        ->completed()
        ->create();

    $refund = Refund::factory()
        ->for($tenant)
        ->for($user)
        ->forTransaction($transaction->id)
        ->pending()
        ->create();

    actingAs($user)
        ->post("/api/{tenant}/billing/refunds/{$refund->id}/cancel")
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Refund cancelled successfully',
        ]);

    expect($refund->fresh()->status)->toBe('cancelled');
});

it('cannot process already processed refund', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    $transaction = Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->payment()
        ->completed()
        ->create();

    $refund = Refund::factory()
        ->for($tenant)
        ->for($user)
        ->forTransaction($transaction->id)
        ->processed()
        ->create();

    actingAs($user)
        ->post("/api/{tenant}/billing/refunds/{$refund->id}/process")
        ->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Refund has already been processed',
        ]);
});

it('can get refund summary', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    Refund::factory()
        ->for($tenant)
        ->for($user)
        ->processed()
        ->count(2)
        ->create();

    Refund::factory()
        ->for($tenant)
        ->for($user)
        ->pending()
        ->count(1)
        ->create();

    actingAs($user)
        ->get('/api/{tenant}/billing/refunds/summary')
        ->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'summary',
                'period',
            ],
        ]);
});

it('requires authentication to access refund endpoints', function () {
    get('/api/test-tenant/billing/refunds')
        ->assertUnauthorized();

    get('/api/test-tenant/billing/refunds/1')
        ->assertUnauthorized();
});
