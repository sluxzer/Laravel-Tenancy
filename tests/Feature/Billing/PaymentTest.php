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

it('can list payment transactions', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->payment()
        ->count(3)
        ->create();

    actingAs($user)
        ->get('/api/{tenant}/billing/payments')
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

it('can get a specific payment transaction', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    $payment = Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->payment()
        ->create();

    actingAs($user)
        ->get("/api/{tenant}/billing/payments/{$payment->id}")
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'type' => 'payment',
            ],
        ]);
});

it('can create a payment transaction', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    $invoice = Invoice::factory()->for($tenant)->for($user)->create();

    $paymentData = [
        'invoice_id' => $invoice->id,
        'amount' => 100.00,
        'currency' => 'USD',
        'gateway' => 'stripe',
        'description' => 'Test payment',
    ];

    actingAs($user)
        ->post('/api/{tenant}/billing/payments', $paymentData)
        ->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Payment created successfully',
        ]);
});

it('can filter payments by status', function () {
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
        ->payment()
        ->pending()
        ->count(1)
        ->create();

    actingAs($user)
        ->get('/api/{tenant}/billing/payments?status=completed')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('can filter payments by provider', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->payment()
        ->withProvider('stripe')
        ->count(2)
        ->create();

    Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->payment()
        ->withProvider('paypal')
        ->count(1)
        ->create();

    actingAs($user)
        ->get('/api/{tenant}/billing/payments?provider=stripe')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('cannot cancel processed payment', function () {
    $tenant = tenancy()->tenant;
    $user = User::factory()->create();

    $payment = Transaction::factory()
        ->for($tenant)
        ->for($user)
        ->payment()
        ->completed()
        ->create();

    actingAs($user)
        ->post("/api/{tenant}/billing/payments/{$payment->id}/cancel")
        ->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Cannot cancel processed payment',
        ]);
});

it('requires authentication to access payment endpoints', function () {
    get('/api/test-tenant/billing/payments')
        ->assertUnauthorized();

    get('/api/test-tenant/billing/payments/1')
        ->assertUnauthorized();
});
