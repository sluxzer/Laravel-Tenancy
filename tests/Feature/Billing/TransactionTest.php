<?php

declare(strict_types=1);

use App\Models\Invoice;
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

it('can list transactions', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->count(3)
        ->create();

    actingAs($user)
        ->get('/api/{tenant}/billing/transactions')
        ->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'amount',
                    'currency',
                    'status',
                    'provider',
                ],
            ],
        ]);
});

it('can get a specific transaction', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    $transaction = Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->create();

    actingAs($user)
        ->get("/api/{tenant}/billing/transactions/{$transaction->id}")
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $transaction->id,
            ],
        ]);
});

it('can create a transaction', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    $invoice = Invoice::factory()->for($tenant)->for($user)->create();

    $transactionData = [
        'invoice_id' => $invoice->id,
        'type' => 'charge',
        'provider' => 'manual',
        'amount' => 50.00,
        'currency' => 'USD',
        'status' => 'completed',
        'description' => 'Test transaction',
    ];

    actingAs($user)
        ->post('/api/{tenant}/billing/transactions', $transactionData)
        ->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Transaction created successfully',
        ]);
});

it('can filter transactions by type', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->payment()
        ->count(2)
        ->create();

    Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->refund()
        ->count(1)
        ->create();

    actingAs($user)
        ->get('/api/{tenant}/billing/transactions?type=payment')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('can filter transactions by status', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->completed()
        ->count(2)
        ->create();

    Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->pending()
        ->count(1)
        ->create();

    actingAs($user)
        ->get('/api/{tenant}/billing/transactions?status=completed')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('can update a transaction', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    $transaction = Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->pending()
        ->create();

    actingAs($user)
        ->put("/api/{tenant}/billing/transactions/{$transaction->id}", [
            'status' => 'completed',
            'description' => 'Updated transaction',
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Transaction updated successfully',
        ]);

    expect($transaction->fresh()->status)->toBe('completed');
});

it('can get transaction summary', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->payment()
        ->completed()
        ->count(2)
        ->create();

    Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->refund()
        ->completed()
        ->count(1)
        ->create();

    actingAs($user)
        ->get('/api/{tenant}/billing/transactions/summary')
        ->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'summary',
                'period',
            ],
        ]);
});

it('requires authentication to access transaction endpoints', function () {
    get('/api/test-tenant/billing/transactions')
        ->assertUnauthorized();

    get('/api/test-tenant/billing/transactions/1')
        ->assertUnauthorized();
});
