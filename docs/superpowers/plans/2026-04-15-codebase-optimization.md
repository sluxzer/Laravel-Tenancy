# Codebase Optimization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement Clean Architecture with Repository, Service, FormRequest, and API Resource layers across the entire SaaS Tenancy Starter backend application.

**Architecture:** Clean Architecture with dependency inversion. Controllers handle HTTP only, Services contain business logic, Repositories abstract data access, FormRequests handle validation, API Resources transform responses. All layers connected via interfaces.

**Tech Stack:** Laravel 13, PHP 8.4, Pest 4, Laravel Sanctum 4, Laravel Wayfinder 0

---

## File Structure Map

**New Files to Create:**

### Repositories Layer
- `app/Repositories/Contracts/` - Interfaces for all repositories
  - `SubscriptionRepositoryInterface.php`
  - `NotificationRepositoryInterface.php`
  - `VoucherRepositoryInterface.php`
  - `PlanRepositoryInterface.php`
  - `InvoiceRepositoryInterface.php`
  - `PaymentRepositoryInterface.php`
  - `UserRepositoryInterface.php`
  - `TenantRepositoryInterface.php`
  - `ActivityRepositoryInterface.php`
  - `ExportJobRepositoryInterface.php`
  - `ImportJobRepositoryInterface.php`
  - `CustomReportRepositoryInterface.php`
  - `WebhookRepositoryInterface.php`
  - `GdprDeletionRequestRepositoryInterface.php`
  - `TransactionRepositoryInterface.php`
  - `RefundRepositoryInterface.php`
  - `InvitationRepositoryInterface.php`
  - `FeatureFlagRepositoryInterface.php`
  - `TaxRateRepositoryInterface.php`

- `app/Repositories/Eloquent/` - Implementations
  - `BaseRepository.php`
  - `SubscriptionRepository.php`
  - `NotificationRepository.php`
  - `VoucherRepository.php`
  - `PlanRepository.php`
  - `InvoiceRepository.php`
  - `PaymentRepository.php`
  - `UserRepository.php`
  - `TenantRepository.php`
  - `ActivityRepository.php`
  - `ExportJobRepository.php`
  - `ImportJobRepository.php`
  - `CustomReportRepository.php`
  - `WebhookRepository.php`
  - `GdprDeletionRequestRepository.php`
  - `TransactionRepository.php`
  - `RefundRepository.php`
  - `InvitationRepository.php`
  - `FeatureFlagRepository.php`
  - `TaxRateRepository.php`

### Services Layer (Refactor existing + Create new)
- `app/Services/Contracts/` - Service interfaces
  - `SubscriptionServiceInterface.php`
  - `NotificationServiceInterface.php`
  - `PaymentServiceInterface.php`
  - `InvoiceServiceInterface.php`
  - `VoucherServiceInterface.php`

### Form Requests Layer (60+ files)
- `app/Http/Requests/Billing/`
  - `CreateSubscriptionRequest.php`
  - `UpdateSubscriptionRequest.php`
  - `CancelSubscriptionRequest.php`
  - `PauseSubscriptionRequest.php`
  - `ResumeSubscriptionRequest.php`
  - `UpgradeSubscriptionRequest.php`
  - `DowngradeSubscriptionRequest.php`
  - `RenewSubscriptionRequest.php`
  - `ApplyVoucherRequest.php`
  - `CreatePaymentRequest.php`
  - `ProcessPaymentRequest.php`
  - `CreateInvoiceRequest.php`
  - `UpdateInvoiceRequest.php`
  - `CreateRefundRequest.php`
  - `GetPaymentMethodsRequest.php`
  - `AddPaymentMethodRequest.php`

- `app/Http/Requests/Management/`
  - `CreateNotificationRequest.php`
  - `UpdateNotificationRequest.php`
  - `BulkSendNotificationRequest.php`
  - `MarkNotificationReadRequest.php`
  - `MarkAllNotificationsReadRequest.php`
  - `UpdateNotificationPreferenceRequest.php`
  - `CreateActivityRequest.php`
  - `CreateInvitationRequest.php`
  - `UpdateInvitationRequest.php`

- `app/Http/Requests/Admin/`
  - `CreateVoucherRequest.php`
  - `UpdateVoucherRequest.php`
  - `GenerateVoucherRequest.php`
  - `ValidateVoucherRequest.php`
  - `BulkGenerateVoucherRequest.php`
  - `CreatePlanRequest.php`
  - `UpdatePlanRequest.php`
  - `CreateTaxRateRequest.php`
  - `UpdateTaxRateRequest.php`
  - `CreateFeatureFlagRequest.php`
  - `UpdateFeatureFlagRequest.php`
  - `CreateCurrencyRequest.php`
  - `UpdateCurrencyRequest.php`
  - `CreateExchangeRateRequest.php`
  - `UpdateExchangeRateRequest.php`
  - `CreateInvitationRequest.php` (Admin version)
  - `UpdateInvitationRequest.php` (Admin version)

- `app/Http/Requests/Settings/`
  - `UpdateProfileRequest.php`
  - `DeleteAccountRequest.php`
  - `UpdatePasswordRequest.php`
  - `EnableTwoFactorRequest.php`
  - `DisableTwoFactorRequest.php`
  - `ConfirmTwoFactorRequest.php`

- `app/Http/Requests/Usage/`
  - `CreateUsageMetricRequest.php`
  - `UpdateUsageMetricRequest.php`
  - `CreateUsagePricingRequest.php`
  - `UpdateUsagePricingRequest.php`
  - `CreateUsageAlertRequest.php`
  - `UpdateUsageAlertRequest.php`

- `app/Http/Requests/Report/`
  - `CreateCustomReportRequest.php`
  - `UpdateCustomReportRequest.php`
  - `RunCustomReportRequest.php`
  - `ScheduleReportRequest.php`
  - `DuplicateReportRequest.php`
  - `CreateScheduledReportRequest.php`
  - `UpdateScheduledReportRequest.php`
  - `RunScheduledReportRequest.php`

- `app/Http/Requests/Export/`
  - `CreateExportJobRequest.php`
  - `CancelExportJobRequest.php`
  - `GetExportStatusRequest.php`

- `app/Http/Requests/Import/`
  - `ValidateImportRequest.php`
  - `PreviewImportRequest.php`
  - `CreateImportJobRequest.php`
  - `CancelImportJobRequest.php`
  - `GetImportTemplateRequest.php`
  - `DownloadTemplateRequest.php`

- `app/Http/Requests/Gdpr/`
  - `ExportUserDataRequest.php`
  - `DownloadUserDataRequest.php`
  - `RequestDeletionRequest.php`
  - `ConfirmDeletionRequest.php`
  - `CancelDeletionRequest.php`
  - `UpdateConsentRequest.php`
  - `AnonymizeUserRequest.php`

### API Resources Layer (30+ files)
- `app/Http/Resources/Billing/`
  - `SubscriptionResource.php`
  - `PlanResource.php`
  - `InvoiceResource.php`
  - `PaymentResource.php`
  - `RefundResource.php`
  - `TransactionResource.php`

- `app/Http/Resources/Management/`
  - `NotificationResource.php`
  - `NotificationPreferenceResource.php`
  - `ActivityResource.php`
  - `InvitationResource.php`

- `app/Http/Resources/Admin/`
  - `VoucherResource.php`
  - `TaxRateResource.php`
  - `FeatureFlagResource.php`
  - `CurrencyResource.php`
  - `ExchangeRateResource.php`

- `app/Http/Resources/User/`
  - `UserResource.php`
  - `UserProfileResource.php`

- `app/Http/Resources/Report/`
  - `CustomReportResource.php`
  - `ReportTemplateResource.php`
  - `ScheduledReportResource.php`

- `app/Http/Resources/Export/`
  - `ExportJobResource.php`

- `app/Http/Resources/Import/`
  - `ImportJobResource.php`

- `app/Http/Resources/Gdpr/`
  - `GdprDeletionRequestResource.php`
  - `UserDataExportResource.php`

### Exceptions Layer
- `app/Exceptions/DomainException.php`
- `app/Exceptions/SubscriptionException.php`
- `app/Exceptions/PaymentException.php`
- `app/Exceptions/InvoiceException.php`
- `app/Exceptions/NotificationException.php`
- `app/Exceptions/VoucherException.php`
- `app/Exceptions/ValidationException.php`
- `app/Exceptions/AuthenticationException.php`
- `app/Exceptions/AuthorizationException.php`

### Helpers
- `app/Http/Resources/JsonResourceCollection.php`

**Files to Modify:**
- All 28 Controllers (refactor to thin layer)
- Existing Services (refactor to use repositories)
- `app/Providers/AppServiceProvider.php` (add repository bindings)
- `app/Exceptions/Handler.php` (add exception rendering)
- Routes files (ensure FormRequests are used)

---

## Phase 1: Repository Layer Foundation

### Task 1: Create BaseRepository

**Files:**
- Create: `app/Repositories/Eloquent/BaseRepository.php`

- [ ] **Step 1: Write the test for BaseRepository**

Create: `tests/Unit/Repositories/BaseRepositoryTest.php`

```php
<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->model = new Plan();
    $this->repository = new BaseRepository($this->model);
});

it('can find a model by id', function () {
    $plan = Plan::factory()->create(['name' => 'Test Plan']);

    $result = $this->repository->find($plan->id);

    expect($result)->toBeInstanceOf(Plan::class)
        ->and($result->id)->toBe($plan->id)
        ->and($result->name)->toBe('Test Plan');
});

it('returns null when model not found', function () {
    $result = $this->repository->find(999999);

    expect($result)->toBeNull();
});

it('can find a model or fail', function () {
    $plan = Plan::factory()->create();

    $result = $this->repository->findOrFail($plan->id);

    expect($result)->toBeInstanceOf(Plan::class)
        ->and($result->id)->toBe($plan->id);
});

it('throws exception when findOrFail fails', function () {
    $this->repository->findOrFail(999999);
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('can get all models', function () {
    Plan::factory()->count(3)->create();

    $result = $this->repository->all();

    expect($result)->toHaveCount(3)
        ->and($result)->each->toBeInstanceOf(Plan::class);
});

it('can create a model', function () {
    $data = [
        'name' => 'New Plan',
        'slug' => 'new-plan',
        'price_monthly' => 29.99,
        'currency_code' => 'USD',
        'is_active' => true,
    ];

    $result = $this->repository->create($data);

    expect($result)->toBeInstanceOf(Plan::class)
        ->and($result->name)->toBe('New Plan')
        ->and($result->slug)->toBe('new-plan')
        ->and(Plan::where('slug', 'new-plan')->exists())->toBeTrue();
});

it('can update a model', function () {
    $plan = Plan::factory()->create(['name' => 'Old Name']);

    $result = $this->repository->update($plan, ['name' => 'New Name']);

    expect($result)->toBeTrue()
        ->and($plan->fresh()->name)->toBe('New Name');
});

it('can delete a model', function () {
    $plan = Plan::factory()->create();

    $result = $this->repository->delete($plan);

    expect($result)->toBeTrue()
        ->and(Plan::where('id', $plan->id)->exists())->toBeFalse();
});

it('can paginate models', function () {
    Plan::factory()->count(25)->create();

    $result = $this->repository->paginate(10);

    expect($result)->toBeInstanceOf(Illuminate\Pagination\LengthAwarePaginator::class)
        ->and($result->total())->toBe(25)
        ->and($result->perPage())->toBe(10);
});

it('can chain where conditions', function () {
    Plan::factory()->create(['name' => 'Active Plan', 'is_active' => true]);
    Plan::factory()->create(['name' => 'Inactive Plan', 'is_active' => false]);

    $result = $this->repository
        ->where('is_active', true)
        ->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->name)->toBe('Active Plan');
});

it('can chain order by', function () {
    Plan::factory()->create(['name' => 'A Plan', 'price_monthly' => 10]);
    Plan::factory()->create(['name' => 'B Plan', 'price_monthly' => 20]);
    Plan::factory()->create(['name' => 'C Plan', 'price_monthly' => 30]);

    $result = $this->repository
        ->orderBy('price_monthly', 'desc')
        ->get();

    expect($result->first()->name)->toBe('C Plan')
        ->and($result->last()->name)->toBe('A Plan');
});

it('can chain with relationships', function () {
    $plan = Plan::factory()->hasFeatures()->create();

    $result = $this->repository
        ->with('features')
        ->find($plan->id);

    expect($result->relationLoaded('features'))->toBeTrue()
        ->and($result->features)->not->toBeEmpty();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=BaseRepositoryTest`

Expected: FAIL - BaseRepository class does not exist

- [ ] **Step 3: Create BaseRepository**

Create: `app/Repositories/Eloquent/BaseRepository.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function find(string|int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findOrFail(string|int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function paginate(
        int $perPage = 20,
        array $columns = ['*'],
        string $pageName = 'page'
    ): LengthAwarePaginator {
        return $this->model->paginate($perPage, $columns, $pageName);
    }

    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function with(array $relations): self
    {
        $this->model = $this->model->with($relations);

        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        $this->model = $this->model->where($column, $operator, $value);

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->model = $this->model->orderBy($column, $direction);

        return $this;
    }

    public function get(): Collection
    {
        return $this->model->get();
    }

    public function first(): ?Model
    {
        return $this->model->first();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=BaseRepositoryTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Repositories/Eloquent/BaseRepository.php tests/Unit/Repositories/BaseRepositoryTest.php
git commit -m "feat: add BaseRepository with CRUD operations

- Create abstract BaseRepository with common CRUD methods
- Add support for query building (where, orderBy, with)
- Add comprehensive unit tests
- Provides foundation for all concrete repositories"
```

### Task 2: Create SubscriptionRepositoryInterface

**Files:**
- Create: `app/Repositories/Contracts/SubscriptionRepositoryInterface.php`

- [ ] **Step 1: Create the interface**

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SubscriptionRepositoryInterface
{
    public function find(string|int $id): ?Subscription;

    public function findOrFail(string|int $id): Subscription;

    public function create(array $data): Subscription;

    public function update(Subscription $subscription, array $data): bool;

    public function delete(Subscription $subscription): bool;

    public function getActiveForUser(int $userId): ?Subscription;

    public function getActiveForTenant(int $tenantId): Collection;

    public function getByTenant(int $tenantId, ?string $status, int $perPage): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?Subscription;

    public function findForUpgrade(Subscription $subscription, Plan $newPlan): bool;

    public function countForTenant(int $tenantId, ?string $status = null): int;
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Repositories/Contracts/SubscriptionRepositoryInterface.php
git commit -m "feat: add SubscriptionRepositoryInterface

- Define contract for subscription data access
- Include methods for user/tenant queries
- Include upgrade validation method
- Provides abstraction for dependency injection"
```

### Task 3: Create SubscriptionRepository Implementation

**Files:**
- Create: `app/Repositories/Eloquent/SubscriptionRepository.php`
- Test: `tests/Unit/Repositories/SubscriptionRepositoryTest.php`

- [ ] **Step 1: Write the failing test**

Create: `tests/Unit/Repositories/SubscriptionRepositoryTest.php`

```php
<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Eloquent\SubscriptionRepository;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    tenancy()->initialize($this->tenant);
    
    $this->repository = app(SubscriptionRepositoryInterface::class);
});

it('can get active subscription for user', function () {
    $user = User::factory()->for($this->tenant)->create();
    $plan = Plan::factory()->create();
    
    $subscription = Subscription::factory()
        ->for($user)
        ->for($plan)
        ->active()
        ->create(['ends_at' => now()->addMonth()]);
    
    $result = $this->repository->getActiveForUser($user->id);
    
    expect($result)->toBeInstanceOf(Subscription::class)
        ->and($result->id)->toBe($subscription->id)
        ->and($result->plan->id)->toBe($plan->id);
});

it('returns null when user has no active subscription', function () {
    $user = User::factory()->for($this->tenant)->create();
    
    $result = $this->repository->getActiveForUser($user->id);
    
    expect($result)->toBeNull();
});

it('can get active subscriptions for tenant', function () {
    $plan = Plan::factory()->create();
    
    Subscription::factory()
        ->for(User::factory()->for($this->tenant))
        ->for($plan)
        ->active()
        ->create();
    
    Subscription::factory()
        ->for(User::factory()->for($this->tenant))
        ->for($plan)
        ->create(['status' => 'cancelled']);
    
    $result = $this->repository->getActiveForTenant($this->tenant->id);
    
    expect($result)->toHaveCount(1)
        ->and($result->first()->status)->toBe('active');
});

it('can get subscriptions by tenant with status filter', function () {
    $plan = Plan::factory()->create();
    
    Subscription::factory()
        ->for(User::factory()->for($this->tenant))
        ->for($plan)
        ->create(['status' => 'active']);
    
    Subscription::factory()
        ->for(User::factory()->for($this->tenant))
        ->for($plan)
        ->create(['status' => 'cancelled']);
    
    $result = $this->repository->getByTenant($this->tenant->id, 'cancelled', 20);
    
    expect($result)->toHaveCount(1)
        ->and($result->first()->status)->toBe('cancelled');
});

it('can find subscription by tenant and id', function () {
    $user = User::factory()->for($this->tenant)->create();
    $plan = Plan::factory()->create();
    
    $subscription = Subscription::factory()
        ->for($user)
        ->for($plan)
        ->create();
    
    Subscription::factory()
        ->for(User::factory()->for(Tenant::factory()->create()))
        ->for($plan)
        ->create();
    
    $result = $this->repository->findByTenant($this->tenant->id, $subscription->id);
    
    expect($result)->toBeInstanceOf(Subscription::class)
        ->and($result->id)->toBe($subscription->id);
});

it('can count subscriptions for tenant', function () {
    Subscription::factory()
        ->for(User::factory()->for($this->tenant))
        ->for(Plan::factory())
        ->count(3)
        ->create(['status' => 'active']);
    
    Subscription::factory()
        ->for(User::factory()->for($this->tenant))
        ->for(Plan::factory())
        ->count(2)
        ->create(['status' => 'cancelled']);
    
    $result = $this->repository->countForTenant($this->tenant->id, 'active');
    
    expect($result)->toBe(3);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SubscriptionRepositoryTest`

Expected: FAIL - SubscriptionRepository does not exist

- [ ] **Step 3: Create SubscriptionRepository**

Create: `app/Repositories/Eloquent/SubscriptionRepository.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Plan;
use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    public function __construct(Subscription $model)
    {
        parent::__construct($model);
    }

    public function getActiveForUser(int $userId): ?Subscription
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->with('plan')
            ->first();
    }

    public function getActiveForTenant(int $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with(['plan', 'user'])
            ->get();
    }

    public function getByTenant(
        int $tenantId,
        ?string $status = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = $this->model->where('tenant_id', $tenantId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query
            ->with(['plan', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findByTenant(int $tenantId, string|int $id): ?Subscription
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with('plan')
            ->first();
    }

    public function findForUpgrade(Subscription $subscription, Plan $newPlan): bool
    {
        return $this->model
            ->where('id', $subscription->id)
            ->where('status', 'active')
            ->whereExists(fn ($query) => $query
                ->from('plans')
                ->whereColumn('plans.id', 'subscriptions.plan_id')
                ->where('price_monthly', '<', $newPlan->price_monthly)
            )
            ->exists();
    }

    public function countForTenant(int $tenantId, ?string $status = null): int
    {
        $query = $this->model->where('tenant_id', $tenantId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->count();
    }
}
```

- [ ] **Step 4: Register the binding in AppServiceProvider**

Modify: `app/Providers/AppServiceProvider.php`

Add to `register()` method:

```php
$this->app->bind(
    \App\Repositories\Contracts\SubscriptionRepositoryInterface::class,
    \App\Repositories\Eloquent\SubscriptionRepository::class
);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=SubscriptionRepositoryTest`

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Repositories/Eloquent/SubscriptionRepository.php tests/Unit/Repositories/SubscriptionRepositoryTest.php app/Providers/AppServiceProvider.php
git commit -m "feat: add SubscriptionRepository implementation

- Implement SubscriptionRepositoryInterface using Eloquent
- Add tenant-scoped query methods
- Add active subscription queries for user/tenant
- Register repository binding in AppServiceProvider
- Add comprehensive unit tests"
```

### Task 4: Create Remaining Repository Interfaces

**Files:**
- Create: Multiple repository interface files

- [ ] **Step 1: Create NotificationRepositoryInterface**

Create: `app/Repositories/Contracts/NotificationRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    public function find(string|int $id): ?Notification;

    public function findOrFail(string|int $id): Notification;

    public function create(array $data): Notification;

    public function update(Notification $notification, array $data): bool;

    public function delete(Notification $notification): bool;

    public function getByTenant(int $tenantId, ?string $status = null, ?string $type = null, ?int $userId = null, int $perPage = 20): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?Notification;

    public function markAsRead(int $notificationId): bool;

    public function markAllAsReadForUser(int $userId): int;

    public function getUnreadCountForUser(int $userId): int;

    public function getByUser(int $userId, int $limit = 20): Collection;
}
```

- [ ] **Step 2: Create VoucherRepositoryInterface**

Create: `app/Repositories/Contracts/VoucherRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Voucher;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface VoucherRepositoryInterface
{
    public function find(string|int $id): ?Voucher;

    public function findOrFail(string|int $id): Voucher;

    public function create(array $data): Voucher;

    public function update(Voucher $voucher, array $data): bool;

    public function delete(Voucher $voucher): bool;

    public function findByCode(string $code): ?Voucher;

    public function getActiveVouchers(?string $type = null, ?string $search = null, ?bool $isActive = null, int $perPage = 20): LengthAwarePaginator;

    public function getVouchersForPlan(int $planId): Collection;

    public function incrementUsage(int $voucherId): bool;

    public function canUseVoucher(Voucher $voucher, ?User $user = null, ?Plan $plan = null): bool;
}
```

- [ ] **Step 3: Create PlanRepositoryInterface**

Create: `app/Repositories/Contracts/PlanRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PlanRepositoryInterface
{
    public function find(string|int $id): ?Plan;

    public function findOrFail(string|int $id): Plan;

    public function create(array $data): Plan;

    public function update(Plan $plan, array $data): bool;

    public function delete(Plan $plan): bool;

    public function getActivePlans(): Collection;

    public function getAllPlans(int $perPage = 20): LengthAwarePaginator;

    public function findBySlug(string $slug): ?Plan;

    public function getPopularPlans(): Collection;
}
```

- [ ] **Step 4: Create InvoiceRepositoryInterface**

Create: `app/Repositories/Contracts/InvoiceRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface InvoiceRepositoryInterface
{
    public function find(string|int $id): ?Invoice;

    public function findOrFail(string|int $id): Invoice;

    public function create(array $data): Invoice;

    public function update(Invoice $invoice, array $data): bool;

    public function delete(Invoice $invoice): bool;

    public function getByTenant(int $tenantId, ?string $status = null, int $perPage = 20): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?Invoice;

    public function getBySubscription(int $subscriptionId): Collection;

    public function getOverdueInvoices(int $tenantId): Collection;
}
```

- [ ] **Step 5: Create PaymentRepositoryInterface**

Create: `app/Repositories/Contracts/PaymentRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface
{
    public function find(string|int $id): ?Payment;

    public function findOrFail(string|int $id): Payment;

    public function create(array $data): Payment;

    public function update(Payment $payment, array $data): bool;

    public function delete(Payment $payment): bool;

    public function getByTenant(int $tenantId, ?string $status = null, ?string $paymentMethod = null, int $perPage = 20): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?Payment;

    public function getByInvoice(int $invoiceId): Collection;
}
```

- [ ] **Step 6: Create UserRepositoryInterface**

Create: `app/Repositories/Contracts/UserRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function find(string|int $id): ?User;

    public function findOrFail(string|int $id): User;

    public function create(array $data): User;

    public function update(User $user, array $data): bool;

    public function delete(User $user): bool;

    public function getByTenant(int $tenantId, int $perPage = 20): LengthAwarePaginator;

    public function findByEmail(string $email): ?User;

    public function findByTenantAndEmail(int $tenantId, string $email): ?User;
}
```

- [ ] **Step 7: Create remaining repository interfaces**

Create: `app/Repositories/Contracts/TenantRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Tenant;

interface TenantRepositoryInterface
{
    public function find(string|int $id): ?Tenant;

    public function findOrFail(string|int $id): Tenant;

    public function create(array $data): Tenant;

    public function update(Tenant $tenant, array $data): bool;

    public function delete(Tenant $tenant): bool;

    public function findByDomain(string $domain): ?Tenant;
}
```

Create: `app/Repositories/Contracts/ActivityRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ActivityRepositoryInterface
{
    public function find(string|int $id): ?Activity;

    public function findOrFail(string|int $id): Activity;

    public function create(array $data): Activity;

    public function delete(Activity $activity): bool;

    public function getByTenant(int $tenantId, ?string $type = null, ?int $userId = null, int $perPage = 20): LengthAwarePaginator;

    public function getByUser(int $userId, int $limit = 50): Collection;
}
```

Create: `app/Repositories/Contracts/ExportJobRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ExportJob;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ExportJobRepositoryInterface
{
    public function find(string|int $id): ?ExportJob;

    public function findOrFail(string|int $id): ExportJob;

    public function create(array $data): ExportJob;

    public function update(ExportJob $job, array $data): bool;

    public function delete(ExportJob $job): bool;

    public function getByTenant(int $tenantId, ?string $status = null, ?string $type = null, int $perPage = 20): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?ExportJob;
}
```

Create: `app/Repositories/Contracts/CustomReportRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\CustomReport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomReportRepositoryInterface
{
    public function find(string|int $id): ?CustomReport;

    public function findOrFail(string|int $id): CustomReport;

    public function create(array $data): CustomReport;

    public function update(CustomReport $report, array $data): bool;

    public function delete(CustomReport $report): bool;

    public function getByTenant(int $tenantId, ?bool $isActive = null, ?string $type = null, int $perPage = 20): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?CustomReport;

    public function getActiveReports(int $tenantId): Collection;
}
```

Create: `app/Repositories/Contracts/WebhookRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface WebhookRepositoryInterface
{
    public function find(string|int $id): ?Webhook;

    public function findOrFail(string|int $id): Webhook;

    public function create(array $data): Webhook;

    public function update(Webhook $webhook, array $data): bool;

    public function delete(Webhook $webhook): bool;

    public function getByTenant(int $tenantId, ?bool $isActive = null, int $perPage = 20): LengthAwarePaginator;

    public function findByTenant(int $tenantId, string|int $id): ?Webhook;

    public function getByEvent(int $tenantId, string $event): Collection;
}
```

Create: `app/Repositories/Contracts/GdprDeletionRequestRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\GdprDeletionRequest;

interface GdprDeletionRequestRepositoryInterface
{
    public function find(string|int $id): ?GdprDeletionRequest;

    public function findOrFail(string|int $id): GdprDeletionRequest;

    public function create(array $data): GdprDeletionRequest;

    public function update(GdprDeletionRequest $request, array $data): bool;

    public function delete(GdprDeletionRequest $request): bool;

    public function findByUser(int $userId): ?GdprDeletionRequest;

    public function findByToken(string $token): ?GdprDeletionRequest;
}
```

Create: `app/Repositories/Contracts/TransactionRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    public function find(string|int $id): ?Transaction;

    public function findOrFail(string|int $id): Transaction;

    public function create(array $data): Transaction;

    public function update(Transaction $transaction, array $data): bool;

    public function delete(Transaction $transaction): bool;

    public function getByTenant(int $tenantId, ?string $type = null, int $perPage = 20): LengthAwarePaginator;
}
```

Create: `app/Repositories/Contracts/RefundRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Refund;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface RefundRepositoryInterface
{
    public function find(string|int $id): ?Refund;

    public function findOrFail(string|int $id): Refund;

    public function create(array $data): Refund;

    public function update(Refund $refund, array $data): bool;

    public function delete(Refund $refund): bool;

    public function getByTenant(int $tenantId, ?string $status = null, int $perPage = 20): LengthAwarePaginator;

    public function getByPayment(int $paymentId): Collection;
}
```

Create: `app/Repositories/Contracts/InvitationRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface InvitationRepositoryInterface
{
    public function find(string|int $id): ?Invitation;

    public function findOrFail(string|int $id): Invitation;

    public function create(array $data): Invitation;

    public function update(Invitation $invitation, array $data): bool;

    public function delete(Invitation $invitation): bool;

    public function getByToken(string $token): ?Invitation;

    public function getByTenant(int $tenantId, ?string $status = null, int $perPage = 20): LengthAwarePaginator;

    public function getByEmail(string $email): ?Invitation;
}
```

Create: `app/Repositories/Contracts/FeatureFlagRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\FeatureFlag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface FeatureFlagRepositoryInterface
{
    public function find(string|int $id): ?FeatureFlag;

    public function findOrFail(string|int $id): FeatureFlag;

    public function create(array $data): FeatureFlag;

    public function update(FeatureFlag $flag, array $data): bool;

    public function delete(FeatureFlag $flag): bool;

    public function getByTenant(int $tenantId, ?bool $isEnabled = null, int $perPage = 20): LengthAwarePaginator;

    public function findByKey(int $tenantId, string $key): ?FeatureFlag;

    public function getEnabledFlags(int $tenantId): Collection;
}
```

Create: `app/Repositories/Contracts/TaxRateRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TaxRateRepositoryInterface
{
    public function find(string|int $id): ?TaxRate;

    public function findOrFail(string|int $id): TaxRate;

    public function create(array $data): TaxRate;

    public function update(TaxRate $taxRate, array $data): bool;

    public function delete(TaxRate $taxRate): bool;

    public function getAll(?bool $isActive = null, int $perPage = 20): LengthAwarePaginator;

    public function getByCountry(string $countryCode): Collection;
}
```

- [ ] **Step 8: Commit**

```bash
git add app/Repositories/Contracts/
git commit -m "feat: add repository interfaces for all entities

- Create interfaces for 20+ entities
- Define contracts for data access layer
- Enable dependency inversion principle
- Provide abstraction for testing and swapping implementations"
```

### Task 5: Create JsonResourceCollection Helper

**Files:**
- Create: `app/Http/Resources/JsonResourceCollection.php`

- [ ] **Step 1: Write the test**

Create: `tests/Unit/Resources/JsonResourceCollectionTest.php`

```php
<?php

declare(strict_types=1);

use App\Http\Resources\JsonResourceCollection;
use App\Http\Resources\Billing\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    tenancy()->initialize($this->tenant);
});

it('can create paginated response', function () {
    $plan = Plan::factory()->create();
    $user = User::factory()->for($this->tenant)->create();
    
    $subscriptions = Subscription::factory()
        ->for($user)
        ->for($plan)
        ->count(3)
        ->create();
    
    $paginator = Subscription::query()->paginate(2);
    
    $result = JsonResourceCollection::paginated($paginator, SubscriptionResource::class);
    
    expect($result['success'])->toBeTrue()
        ->and($result['data'])->toHaveCount(2)
        ->and($result['pagination']['total'])->toBe(3)
        ->and($result['pagination']['per_page'])->toBe(2)
        ->and($result['pagination']['current_page'])->toBe(1);
});

it('can create single resource response', function () {
    $plan = Plan::factory()->create();
    $user = User::factory()->for($this->tenant)->create();
    $subscription = Subscription::factory()->for($user)->for($plan)->create();
    
    $resource = SubscriptionResource::make($subscription);
    
    $result = JsonResourceCollection::single($resource);
    
    expect($result['success'])->toBeTrue()
        ->and($result['data']['id'])->toBe($subscription->id);
});

it('can create success response', function () {
    $data = ['message' => 'Success'];
    
    $result = JsonResourceCollection::success('Operation completed', $data);
    
    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('Operation completed')
        ->and($result['data'])->toBe($data);
});

it('can create error response', function () {
    $result = JsonResourceCollection::error('Something went wrong', 400, ['field' => ['error']]);
    
    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toBe('Something went wrong')
        ->and($result['errors'])->toBe(['field' => ['error']]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=JsonResourceCollectionTest`

Expected: FAIL - JsonResourceCollection does not exist

- [ ] **Step 3: Create JsonResourceCollection**

Create: `app/Http/Resources/JsonResourceCollection.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class JsonResourceCollection
{
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $resourceClass
    ): array {
        return [
            'success' => true,
            'data' => $resourceClass::collection($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    public static function single(JsonResource $resource): array
    {
        return [
            'success' => true,
            'data' => $resource->resolve(),
        ];
    }

    public static function success(string $message, mixed $data = null): array
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $response;
    }

    public static function error(string $message, int $code = 400, ?array $errors = null): array
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $response;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=JsonResourceCollectionTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Resources/JsonResourceCollection.php tests/Unit/Resources/JsonResourceCollectionTest.php
git commit -m "feat: add JsonResourceCollection helper

- Add helper for consistent API responses
- Support paginated, single, success, and error responses
- Simplify controller response formatting
- Add unit tests for all response types"
```

### Task 6: Create Domain Exception Base Class

**Files:**
- Create: `app/Exceptions/DomainException.php`

- [ ] **Step 1: Write the test**

Create: `tests/Unit/Exceptions/DomainExceptionTest.php`

```php
<?php

declare(strict_types=1);

use App\Exceptions\DomainException;

it('has error code and http status', function () {
    $exception = new DomainException('Test error', 400, 'TEST_ERROR');

    expect($exception->getMessage())->toBe('Test error')
        ->and($exception->getErrorCode())->toBe('TEST_ERROR')
        ->and($exception->getHttpStatus())->toBe(400);
});

it('can render to array', function () {
    $exception = new DomainException('Test error', 400, 'TEST_ERROR');

    $result = $exception->render();

    expect($result)->toBe([
        'success' => false,
        'message' => 'Test error',
        'error_code' => 'TEST_ERROR',
    ]);
});

it('has default error code', function () {
    $exception = new DomainException('Test error');

    expect($exception->getErrorCode())->toBe('DOMAIN_ERROR');
});

it('has default http status', function () {
    $exception = new DomainException('Test error');

    expect($exception->getHttpStatus())->toBe(400);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DomainExceptionTest`

Expected: FAIL - DomainException does not exist

- [ ] **Step 3: Create DomainException**

Create: `app/Exceptions/DomainException.php`

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

abstract class DomainException extends Exception
{
    protected string $errorCode = 'DOMAIN_ERROR';
    protected int $httpStatus = 400;

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function render(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=DomainExceptionTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Exceptions/DomainException.php tests/Unit/Exceptions/DomainExceptionTest.php
git commit -m "feat: add DomainException base class

- Create abstract base for domain exceptions
- Include error code and HTTP status
- Provide render method for JSON responses
- Add unit tests"
```

### Task 7: Create Specific Domain Exceptions

**Files:**
- Create: Multiple exception classes

- [ ] **Step 1: Create SubscriptionException**

Create: `app/Exceptions/SubscriptionException.php`

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

class SubscriptionException extends DomainException
{
    protected string $errorCode = 'SUBSCRIPTION_ERROR';

    public static function alreadyActive(): self
    {
        return new self('User already has an active subscription', 409, 'SUBSCRIPTION_ALREADY_ACTIVE');
    }

    public static function notFound(): self
    {
        return new self('Subscription not found', 404, 'SUBSCRIPTION_NOT_FOUND');
    }

    public static function cannotCancel(string $reason): self
    {
        return new self("Cannot cancel subscription: {$reason}", 400, 'SUBSCRIPTION_CANNOT_CANCEL');
    }

    public static function invalidStatus(string $currentStatus, array $validStatuses): self
    {
        return new self(
            sprintf('Invalid subscription status %s. Required: %s', $currentStatus, implode(', ', $validStatuses)),
            400,
            'SUBSCRIPTION_INVALID_STATUS'
        );
    }

    public static function cannotPause(): self
    {
        return new self('Only active subscriptions can be paused', 400, 'SUBSCRIPTION_CANNOT_PAUSE');
    }

    public static function cannotResume(): self
    {
        return new self('Only paused subscriptions can be resumed', 400, 'SUBSCRIPTION_CANNOT_RESUME');
    }

    public static function cannotRenew(): self
    {
        return new self('Cannot renew this subscription', 400, 'SUBSCRIPTION_CANNOT_RENEW');
    }

    public function __construct(string $message, int $status = 400, string $code = 'SUBSCRIPTION_ERROR')
    {
        parent::__construct($message, $status);
        $this->httpStatus = $status;
        $this->errorCode = $code;
    }
}
```

- [ ] **Step 2: Create PaymentException**

Create: `app/Exceptions/PaymentException.php`

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

class PaymentException extends DomainException
{
    protected string $errorCode = 'PAYMENT_ERROR';

    public static function failed(string $reason): self
    {
        return new self("Payment failed: {$reason}", 400, 'PAYMENT_FAILED');
    }

    public static function gatewayError(string $gateway): self
    {
        return new self("Payment gateway error: {$gateway}", 502, 'PAYMENT_GATEWAY_ERROR');
    }

    public static function insufficientFunds(): self
    {
        return new self('Insufficient funds for this transaction', 402, 'PAYMENT_INSUFFICIENT_FUNDS');
    }

    public static function alreadyProcessed(): self
    {
        return new self('Payment has already been processed', 400, 'PAYMENT_ALREADY_PROCESSED');
    }

    public static function notFound(): self
    {
        return new self('Payment not found', 404, 'PAYMENT_NOT_FOUND');
    }
}
```

- [ ] **Step 3: Create InvoiceException**

Create: `app/Exceptions/InvoiceException.php`

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

class InvoiceException extends DomainException
{
    protected string $errorCode = 'INVOICE_ERROR';

    public static function notFound(): self
    {
        return new self('Invoice not found', 404, 'INVOICE_NOT_FOUND');
    }

    public static function cannotPay(): self
    {
        return new self('This invoice cannot be paid', 400, 'INVOICE_CANNOT_PAY');
    }

    public static function alreadyPaid(): self
    {
        return new self('Invoice has already been paid', 400, 'INVOICE_ALREADY_PAID');
    }

    public static function overdue(): self
    {
        return new self('Invoice is overdue', 400, 'INVOICE_OVERDUE');
    }
}
```

- [ ] **Step 4: Create NotificationException**

Create: `app/Exceptions/NotificationException.php`

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

class NotificationException extends DomainException
{
    protected string $errorCode = 'NOTIFICATION_ERROR';

    public static function notFound(): self
    {
        return new self('Notification not found', 404, 'NOTIFICATION_NOT_FOUND');
    }

    public static function sendFailed(string $reason): self
    {
        return new self("Failed to send notification: {$reason}", 500, 'NOTIFICATION_SEND_FAILED');
    }

    public static function invalidChannel(string $channel): self
    {
        return new self("Invalid notification channel: {$channel}", 400, 'NOTIFICATION_INVALID_CHANNEL');
    }
}
```

- [ ] **Step 5: Create VoucherException**

Create: `app/Exceptions/VoucherException.php`

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

class VoucherException extends DomainException
{
    protected string $errorCode = 'VOUCHER_ERROR';

    public static function notFound(): self
    {
        return new self('Voucher not found', 404, 'VOUCHER_NOT_FOUND');
    }

    public static function expired(): self
    {
        return new self('Voucher has expired', 400, 'VOUCHER_EXPIRED');
    }

    public static function notActive(): self
    {
        return new self('Voucher is not active', 400, 'VOUCHER_NOT_ACTIVE');
    }

    public static function maxUsesReached(): self
    {
        return new self('Voucher has reached maximum uses', 400, 'VOUCHER_MAX_USES_REACHED');
    }

    public static function alreadyUsed(): self
    {
        return new self('You have already used this voucher', 400, 'VOUCHER_ALREADY_USED');
    }

    public static function invalidForPlan(): self
    {
        return new self('This voucher is not valid for the selected plan', 400, 'VOUCHER_INVALID_FOR_PLAN');
    }
}
```

- [ ] **Step 6: Create ValidationException**

Create: `app/Exceptions/ValidationException.php`

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends DomainException
{
    protected string $errorCode = 'VALIDATION_ERROR';
    protected array $errors = [];

    public function __construct(string $message, array $errors = [], int $status = 422)
    {
        parent::__construct($message, $status);
        $this->errors = $errors;
        $this->errorCode = 'VALIDATION_ERROR';
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function render(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'errors' => $this->errors,
        ];
    }
}
```

- [ ] **Step 7: Create AuthenticationException**

Create: `app/Exceptions/AuthenticationException.php`

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

class AuthenticationException extends DomainException
{
    protected string $errorCode = 'AUTHENTICATION_ERROR';

    public static function unauthenticated(): self
    {
        return new self('Unauthenticated', 401, 'UNAUTHENTICATED');
    }

    public static function invalidCredentials(): self
    {
        return new self('Invalid credentials', 401, 'INVALID_CREDENTIALS');
    }

    public static function tokenExpired(): self
    {
        return new self('Authentication token has expired', 401, 'TOKEN_EXPIRED');
    }

    public static function accountLocked(): self
    {
        return new self('Account has been locked', 403, 'ACCOUNT_LOCKED');
    }
}
```

- [ ] **Step 8: Create AuthorizationException**

Create: `app/Exceptions/AuthorizationException.php`

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

class AuthorizationException extends DomainException
{
    protected string $errorCode = 'AUTHORIZATION_ERROR';

    public static function forbidden(): self
    {
        return new self('You do not have permission to perform this action', 403, 'FORBIDDEN');
    }

    public static function notOwner(): self
    {
        return new self('You do not own this resource', 403, 'NOT_OWNER');
    }

    public static function tenantMismatch(): self
    {
        return new self('Resource does not belong to your tenant', 403, 'TENANT_MISMATCH');
    }
}
```

- [ ] **Step 9: Commit**

```bash
git add app/Exceptions/
git commit -m "feat: add domain-specific exception classes

- Create SubscriptionException, PaymentException, InvoiceException
- Create NotificationException, VoucherException, ValidationException
- Create AuthenticationException, AuthorizationException
- Provide static factory methods for common scenarios
- Include HTTP status codes and error codes"
```

### Task 8: Update Exception Handler

**Files:**
- Modify: `app/Exceptions/Handler.php`

- [ ] **Step 1: Update Handler**

Modify: `app/Exceptions/Handler.php`

Replace the `register` method with:

```php
public function register(): void
{
    $this->renderable(function (DomainException $e, Request $request) {
        return $this->jsonResponse($e->render(), $e->getHttpStatus());
    });

    $this->renderable(function (NotFoundHttpException $e, Request $request) {
        return $this->jsonResponse([
            'success' => false,
            'message' => 'Resource not found',
            'error_code' => 'NOT_FOUND',
        ], 404);
    });

    $this->renderable(function (LaravelValidationException $e, Request $request) {
        return $this->jsonResponse([
            'success' => false,
            'message' => 'Validation failed',
            'error_code' => 'VALIDATION_ERROR',
            'errors' => $e->errors(),
        ], 422);
    });

    $this->renderable(function (AuthenticationException $e, Request $request) {
        return $this->jsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
            'error_code' => $e->getErrorCode(),
        ], $e->getHttpStatus());
    });

    $this->renderable(function (AuthorizationException $e, Request $request) {
        return $this->jsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
            'error_code' => $e->getErrorCode(),
        ], $e->getHttpStatus());
    });

    $this->renderable(function (Throwable $e, Request $request) {
        if (config('app.debug')) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'INTERNAL_ERROR',
                'trace' => $e->getTraceAsString(),
            ], 500);
        }

        return $this->jsonResponse([
            'success' => false,
            'message' => 'An internal error occurred',
            'error_code' => 'INTERNAL_ERROR',
        ], 500);
    });
}

protected function jsonResponse(array $data, int $status): JsonResponse
{
    return response()->json($data, $status);
}
```

Add use statements at top:

```php
use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\DomainException;
use Illuminate\Auth\AuthenticationException as LaravelAuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
```

- [ ] **Step 2: Commit**

```bash
git add app/Exceptions/Handler.php
git commit -m "refactor: update exception handler for domain exceptions

- Add rendering for DomainException subclasses
- Format all exceptions as JSON with consistent structure
- Include error codes for client-side handling
- Show trace in debug mode only"
```

---

## Phase 2: Service Layer Refactoring

### Task 9: Create SubscriptionServiceInterface

**Files:**
- Create: `app/Services/Contracts/SubscriptionServiceInterface.php`

- [ ] **Step 1: Create the interface**

```php
<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

interface SubscriptionServiceInterface
{
    public function getActiveSubscription(User $user): ?Subscription;

    public function getForTenant(int $tenantId, ?string $status, int $perPage): \Illuminate\Pagination\LengthAwarePaginator;

    public function create(User $user, Plan $plan, string $paymentMethod, ?string $paymentToken, string $billingCycle): Subscription;

    public function update(Subscription $subscription, array $data): Subscription;

    public function upgrade(Subscription $subscription, Plan $newPlan): Subscription;

    public function downgrade(Subscription $subscription, Plan $newPlan): Subscription;

    public function pause(Subscription $subscription): Subscription;

    public function resume(Subscription $subscription): Subscription;

    public function cancel(Subscription $subscription, ?string $reason = null): Subscription;

    public function renew(Subscription $subscription): Subscription;

    public function applyVoucher(Subscription $subscription, User $user, string $code): void;
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/Contracts/SubscriptionServiceInterface.php
git commit -m "feat: add SubscriptionServiceInterface

- Define contract for subscription business logic
- Include all subscription operations
- Enable dependency injection and testing"
```

### Task 10: Refactor SubscriptionService

**Files:**
- Modify: `app/Services/SubscriptionService.php`
- Test: `tests/Feature/Services/SubscriptionServiceTest.php`

- [ ] **Step 1: Write comprehensive tests**

Create: `tests/Feature/Services/SubscriptionServiceTest.php`

```php
<?php

declare(strict_types=1);

use App\Exceptions\SubscriptionException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\Contracts\SubscriptionServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    tenancy()->initialize($this->tenant);
    $this->service = app(SubscriptionServiceInterface::class);
});

it('can get active subscription for user', function () {
    $user = User::factory()->for($this->tenant)->create();
    $plan = Plan::factory()->create();
    
    $subscription = Subscription::factory()
        ->for($user)
        ->for($plan)
        ->active()
        ->create(['ends_at' => now()->addMonth()]);
    
    $result = $this->service->getActiveSubscription($user);
    
    expect($result)->toBeInstanceOf(Subscription::class)
        ->and($result->id)->toBe($subscription->id);
});

it('returns null when user has no active subscription', function () {
    $user = User::factory()->for($this->tenant)->create();
    
    $result = $this->service->getActiveSubscription($user);
    
    expect($result)->toBeNull();
});

it('can create subscription for user', function () {
    $user = User::factory()->for($this->tenant)->create();
    $plan = Plan::factory()->create(['trial_days' => 14]);
    
    $subscription = $this->service->create(
        $user,
        $plan,
        'card',
        'tok_test',
        'monthly'
    );
    
    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->user_id)->toBe($user->id)
        ->and($subscription->plan_id)->toBe($plan->id)
        ->and($subscription->status)->toBe('active')
        ->and($subscription->trial_ends_at)->not->toBeNull();
    
    assertDatabaseHas('subscriptions', [
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);
});

it('throws exception when creating subscription for user with active subscription', function () {
    $user = User::factory()->for($this->tenant)->create();
    $plan = Plan::factory()->create();
    
    Subscription::factory()
        ->for($user)
        ->for($plan)
        ->active()
        ->create(['ends_at' => now()->addMonth()]);
    
    $this->service->create(
        $user,
        Plan::factory()->create(),
        'card',
        'tok_test',
        'monthly'
    );
})->throws(SubscriptionException::class, 'User already has an active subscription');

it('can pause active subscription', function () {
    $user = User::factory()->for($this->tenant)->create();
    $subscription = Subscription::factory()
        ->for($user)
        ->for(Plan::factory())
        ->active()
        ->create();
    
    $result = $this->service->pause($subscription);
    
    expect($result->status)->toBe('paused')
        ->and($result->grace_period_ends_at)->not->toBeNull();
});

it('throws exception when pausing non-active subscription', function () {
    $user = User::factory()->for($this->tenant)->create();
    $subscription = Subscription::factory()
        ->for($user)
        ->for(Plan::factory())
        ->create(['status' => 'cancelled']);
    
    $this->service->pause($subscription);
})->throws(SubscriptionException::class);

it('can cancel subscription', function () {
    $user = User::factory()->for($this->tenant)->create();
    $subscription = Subscription::factory()
        ->for($user)
        ->for(Plan::factory())
        ->active()
        ->create();
    
    $result = $this->service->cancel($subscription, 'Too expensive');
    
    expect($result->status)->toBe('cancelled')
        ->and($result->cancelled_at)->not->BeNull()
        ->and($result->cancellation_reason)->toBe('Too expensive');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=SubscriptionServiceTest`

Expected: FAIL - Service needs to use repositories

- [ ] **Step 3: Refactor SubscriptionService**

Modify: `app/Services/SubscriptionService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SubscriptionException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Services\Contracts\SubscriptionServiceInterface;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        protected SubscriptionRepositoryInterface $subscriptionRepository,
        protected PaymentRepositoryInterface $paymentRepository,
        protected InvoiceRepositoryInterface $invoiceRepository,
        protected NotificationService $notificationService
    ) {}

    public function getActiveSubscription(User $user): ?Subscription
    {
        return $this->subscriptionRepository->getActiveForUser($user->id);
    }

    public function getForTenant(int $tenantId, ?string $status, int $perPage): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getByTenant($tenantId, $status, $perPage);
    }

    public function create(User $user, Plan $plan, string $paymentMethod, ?string $paymentToken, string $billingCycle): Subscription
    {
        $existing = $this->subscriptionRepository->getActiveForUser($user->id);
        if ($existing) {
            throw SubscriptionException::alreadyActive();
        }

        if (! $plan->is_active) {
            throw new SubscriptionException('Plan is not available');
        }

        return DB::transaction(function () use ($user, $plan, $paymentMethod, $paymentToken, $billingCycle) {
            $subscription = $this->subscriptionRepository->create([
                'tenant_id' => tenancy()->tenant->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'billing_cycle' => $billingCycle,
                'starts_at' => now(),
                'ends_at' => $this->calculateEndDate($billingCycle),
                'trial_ends_at' => $plan->trial_days ? now()->addDays($plan->trial_days) : null,
            ]);

            $amount = $this->getPlanPrice($plan, $billingCycle);
            
            $this->invoiceRepository->create([
                'tenant_id' => $subscription->tenant_id,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'currency_code' => $plan->currency_code,
                'status' => 'pending',
                'due_at' => now(),
            ]);

            $this->notificationService->sendSubscriptionCreated($user, $subscription);

            return $subscription->load('plan');
        });
    }

    public function update(Subscription $subscription, array $data): Subscription
    {
        $this->subscriptionRepository->update($subscription, $data);

        return $subscription->fresh()->load('plan');
    }

    public function upgrade(Subscription $subscription, Plan $newPlan): Subscription
    {
        $currentPlan = $subscription->plan;

        if ($currentPlan->price_monthly >= $newPlan->price_monthly) {
            throw new SubscriptionException('New plan must have higher price for upgrade');
        }

        if (! $this->subscriptionRepository->findForUpgrade($subscription, $newPlan)) {
            throw new SubscriptionException('Cannot upgrade this subscription');
        }

        return DB::transaction(function () use ($subscription, $newPlan, $currentPlan) {
            $proratedAmount = $this->calculateProratedAmount($subscription, $newPlan);
            
            if ($proratedAmount > 0) {
                $this->invoiceRepository->create([
                    'tenant_id' => $subscription->tenant_id,
                    'user_id' => $subscription->user_id,
                    'subscription_id' => $subscription->id,
                    'amount' => $proratedAmount,
                    'currency_code' => $newPlan->currency_code,
                    'status' => 'pending',
                    'type' => 'upgrade',
                    'due_at' => now(),
                ]);
            }

            $this->subscriptionRepository->update($subscription, [
                'plan_id' => $newPlan->id,
            ]);

            $this->notificationService->sendSubscriptionUpgraded(
                $subscription->user,
                $subscription->load('plan')
            );

            return $subscription->refresh()->load('plan');
        });
    }

    public function downgrade(Subscription $subscription, Plan $newPlan): Subscription
    {
        $currentPlan = $subscription->plan;

        if ($currentPlan->price_monthly <= $newPlan->price_monthly) {
            throw new SubscriptionException('New plan must have lower price for downgrade');
        }

        $this->subscriptionRepository->update($subscription, [
            'metadata->downgrade_to_plan_id' => $newPlan->id,
            'metadata->downgrade_at' => $subscription->ends_at->toIso8601String(),
        ]);

        $this->notificationService->sendSubscriptionDowngradeScheduled(
            $subscription->user,
            $subscription->load('plan'),
            $newPlan
        );

        return $subscription->refresh()->load('plan');
    }

    public function pause(Subscription $subscription): Subscription
    {
        if (! in_array($subscription->status, ['active', 'trialing'])) {
            throw SubscriptionException::cannotPause();
        }

        $this->subscriptionRepository->update($subscription, [
            'status' => 'paused',
            'grace_period_ends_at' => now()->addDays(7),
        ]);

        $this->notificationService->sendSubscriptionPaused($subscription->user, $subscription);

        return $subscription->refresh()->load('plan');
    }

    public function resume(Subscription $subscription): Subscription
    {
        if ($subscription->status !== 'paused') {
            throw SubscriptionException::cannotResume();
        }

        $this->subscriptionRepository->update($subscription, [
            'status' => 'active',
            'grace_period_ends_at' => null,
        ]);

        $this->notificationService->sendSubscriptionResumed($subscription->user, $subscription);

        return $subscription->refresh()->load('plan');
    }

    public function cancel(Subscription $subscription, ?string $reason = null): Subscription
    {
        if (! in_array($subscription->status, ['active', 'trialing', 'paused'])) {
            throw SubscriptionException::cannotCancel('Invalid subscription status');
        }

        $this->subscriptionRepository->update($subscription, [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        $this->notificationService->sendSubscriptionCancelled($subscription->user, $subscription);

        return $subscription->refresh()->load('plan');
    }

    public function renew(Subscription $subscription): Subscription
    {
        if (! in_array($subscription->status, ['active', 'trialing'])) {
            throw SubscriptionException::cannotRenew();
        }

        return DB::transaction(function () use ($subscription) {
            $plan = $subscription->plan;
            $billingCycle = $subscription->billing_cycle;
            
            $newEndDate = $subscription->ends_at->copy()->add(
                $this->getIntervalForCycle($billingCycle),
                $this->getUnitForCycle($billingCycle)
            );

            $this->subscriptionRepository->update($subscription, [
                'ends_at' => $newEndDate,
            ]);

            $amount = $this->getPlanPrice($plan, $billingCycle);
            
            $this->invoiceRepository->create([
                'tenant_id' => $subscription->tenant_id,
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'currency_code' => $plan->currency_code,
                'status' => 'pending',
                'due_at' => $subscription->ends_at,
            ]);

            $this->notificationService->sendSubscriptionRenewed($subscription->user, $subscription);

            return $subscription->refresh()->load('plan');
        });
    }

    public function applyVoucher(Subscription $subscription, User $user, string $code): void
    {
        $voucher = app(VoucherService::class)->validate($code, $user, $subscription->plan);
        
        if (! $voucher['valid']) {
            throw SubscriptionException('Invalid voucher: ' . $voucher['message']);
        }

        $this->subscriptionRepository->update($subscription, [
            'metadata->voucher_code' => strtoupper($code),
            'metadata->voucher_discount' => $voucher['discount_amount'],
        ]);

        $this->notificationService->sendVoucherApplied($user, $subscription);
    }

    protected function calculateEndDate(string $billingCycle): Carbon
    {
        return match ($billingCycle) {
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addQuarter(),
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };
    }

    protected function calculateProratedAmount(Subscription $subscription, Plan $newPlan): float
    {
        $daysRemaining = max(1, now()->diffInDays($subscription->ends_at, false));
        $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at) ?: 30;
        
        $priceDifference = $newPlan->price_monthly - $subscription->plan->price_monthly;
        
        return max(0, ($priceDifference / $totalDays) * $daysRemaining);
    }

    protected function getPlanPrice(Plan $plan, string $billingCycle): float
    {
        return match ($billingCycle) {
            'monthly' => $plan->price_monthly,
            'quarterly' => $plan->price_quarterly ?? ($plan->price_monthly * 3),
            'yearly' => $plan->price_yearly ?? ($plan->price_monthly * 12),
            default => $plan->price_monthly,
        };
    }

    protected function getIntervalForCycle(string $billingCycle): int
    {
        return match ($billingCycle) {
            'monthly' => 1,
            'quarterly' => 3,
            'yearly' => 12,
            default => 1,
        };
    }

    protected function getUnitForCycle(string $billingCycle): string
    {
        return match ($billingCycle) {
            'monthly' => 'month',
            'quarterly' => 'month',
            'yearly' => 'year',
            default => 'month',
        };
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=SubscriptionServiceTest`

Expected: PASS (may need to adjust NotificationService mocks)

- [ ] **Step 5: Register binding in AppServiceProvider**

Modify: `app/Providers/AppServiceProvider.php`

Add to `register()` method:

```php
$this->app->bind(
    \App\Services\Contracts\SubscriptionServiceInterface::class,
    \App\Services\SubscriptionService::class
);
```

- [ ] **Step 6: Commit**

```bash
git add app/Services/SubscriptionService.php tests/Feature/Services/SubscriptionServiceTest.php app/Providers/AppServiceProvider.php
git commit -m "refactor: refactor SubscriptionService to use repositories

- Inject repository interfaces instead of direct model access
- Move all business logic to service layer
- Add comprehensive exception handling
- Add comprehensive test coverage
- Implement upgrade, downgrade, pause, resume, cancel, renew operations"
```

### Task 11: Create CreateSubscriptionRequest

**Files:**
- Create: `app/Http/Requests/Billing/CreateSubscriptionRequest.php`
- Test: `tests/Unit/Requests/CreateSubscriptionRequestTest.php`

- [ ] **Step 1: Write the test**

Create: `tests/Unit/Requests/CreateSubscriptionRequestTest.php`

```php
<?php

declare(strict_types=1);

use App\Http\Requests\Billing\CreateSubscriptionRequest;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->request = new CreateSubscriptionRequest();
    $this->activePlan = Plan::factory()->create(['is_active' => true]);
    $this->inactivePlan = Plan::factory()->create(['is_active' => false]);
});

it('passes validation with valid data', function () {
    $validator = Validator::make([
        'plan_id' => $this->activePlan->id,
        'payment_method' => 'card',
        'payment_token' => 'tok_test_123',
        'billing_cycle' => 'monthly',
    ], $this->request->rules());

    expect($validator->passes())->toBeTrue();
});

it('fails when plan_id is missing', function () {
    $validator = Validator::make([
        'payment_method' => 'card',
        'billing_cycle' => 'monthly',
    ], $this->request->rules());

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->has('plan_id'))->toBeTrue();
});

it('fails when plan_id does not exist', function () {
    $validator = Validator::make([
        'plan_id' => 999999,
        'payment_method' => 'card',
        'billing_cycle' => 'monthly',
    ], $this->request->rules());

    expect($validator->passes())->toBeFalse();
});

it('fails when plan is not active', function () {
    $validator = Validator::make([
        'plan_id' => $this->inactivePlan->id,
        'payment_method' => 'card',
        'billing_cycle' => 'monthly',
    ], $this->request->rules());

    expect($validator->passes())->toBeFalse();
});

it('fails when payment_method is invalid', function () {
    $validator = Validator::make([
        'plan_id' => $this->activePlan->id,
        'payment_method' => 'invalid_method',
        'billing_cycle' => 'monthly',
    ], $this->request->rules());

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->has('payment_method'))->toBeTrue();
});

it('fails when billing_cycle is invalid', function () {
    $validator = Validator::make([
        'plan_id' => $this->activePlan->id,
        'payment_method' => 'card',
        'billing_cycle' => 'invalid_cycle',
    ], $this->request->rules());

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->has('billing_cycle'))->toBeTrue();
});

it('validates payment_token format when provided', function () {
    $validator = Validator::make([
        'plan_id' => $this->activePlan->id,
        'payment_method' => 'card',
        'payment_token' => 'invalid token with spaces',
        'billing_cycle' => 'monthly',
    ], $this->request->rules());

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->has('payment_token'))->toBeTrue();
});

it('getPlan returns the plan', function () {
    $validator = Validator::make([
        'plan_id' => $this->activePlan->id,
        'payment_method' => 'card',
        'billing_cycle' => 'monthly',
    ], $this->request->rules());

    $validator->validate();
    
    expect($this->request->getPlan())->toBeInstanceOf(Plan::class)
        ->and($this->request->getPlan()->id)->toBe($this->activePlan->id);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CreateSubscriptionRequestTest`

Expected: FAIL - Request does not exist

- [ ] **Step 3: Create CreateSubscriptionRequest**

Create: `app/Http/Requests/Billing/CreateSubscriptionRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                'min:1',
                'exists:plans,id,is_active,1',
            ],
            'payment_method' => [
                'required',
                'string',
                'in:card,paypal,stripe,xendit',
            ],
            'payment_token' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]+$/',
            ],
            'billing_cycle' => [
                'required',
                'string',
                'in:monthly,yearly,quarterly',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'A plan must be selected',
            'plan_id.exists' => 'The selected plan does not exist or is not available',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method',
            'billing_cycle.required' => 'Billing cycle is required',
            'billing_cycle.in' => 'Invalid billing cycle',
        ];
    }

    public function getPlan(): Plan
    {
        return Plan::findOrFail($this->plan_id);
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=CreateSubscriptionRequestTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Requests/Billing/CreateSubscriptionRequest.php tests/Unit/Requests/CreateSubscriptionRequestTest.php
git commit -m "feat: add CreateSubscriptionRequest

- Create FormRequest for subscription creation
- Validate plan exists and is active
- Validate payment method and billing cycle
- Add helper method to get Plan model
- Include custom error messages"
```

### Task 12: Create Remaining Billing Form Requests

**Files:**
- Create: Multiple Form Request files

- [ ] **Step 1: Create UpdateSubscriptionRequest**

Create: `app/Http/Requests/Billing/UpdateSubscriptionRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:active,cancelled,paused,expired,trialing'],
        ];
    }
}
```

- [ ] **Step 2: Create CancelSubscriptionRequest**

Create: `app/Http/Requests/Billing/CancelSubscriptionRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CancelSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subscription = Subscription::find($this->route('id'));
        return $subscription && $this->user()?->can('cancel', $subscription) ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
```

- [ ] **Step 3: Create PauseSubscriptionRequest**

Create: `app/Http/Requests/Billing/PauseSubscriptionRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class PauseSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subscription = Subscription::find($this->route('id'));
        return $subscription && $this->user()?->can('pause', $subscription) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
```

- [ ] **Step 4: Create ResumeSubscriptionRequest**

Create: `app/Http/Requests/Billing/ResumeSubscriptionRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class ResumeSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subscription = Subscription::find($this->route('id'));
        return $subscription && $this->user()?->can('resume', $subscription) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
```

- [ ] **Step 5: Create UpgradeSubscriptionRequest**

Create: `app/Http/Requests/Billing/UpgradeSubscriptionRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpgradeSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subscription = Subscription::find($this->route('id'));
        return $subscription && $this->user()?->can('upgrade', $subscription) ?? false;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:plans,id,is_active,1'],
        ];
    }

    public function getSubscription(): Subscription
    {
        return Subscription::findOrFail($this->route('id'));
    }

    public function getNewPlan(): Plan
    {
        return Plan::findOrFail($this->plan_id);
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
```

- [ ] **Step 6: Create DowngradeSubscriptionRequest**

Create: `app/Http/Requests/Billing/DowngradeSubscriptionRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DowngradeSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subscription = Subscription::find($this->route('id'));
        return $subscription && $this->user()?->can('downgrade', $subscription) ?? false;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:plans,id,is_active,1'],
        ];
    }

    public function getSubscription(): Subscription
    {
        return Subscription::findOrFail($this->route('id'));
    }

    public function getNewPlan(): Plan
    {
        return Plan::findOrFail($this->plan_id);
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
```

- [ ] **Step 7: Create RenewSubscriptionRequest**

Create: `app/Http/Requests/Billing/RenewSubscriptionRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class RenewSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subscription = Subscription::find($this->route('id'));
        return $subscription && $this->user()?->can('renew', $subscription) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
```

- [ ] **Step 8: Create ApplyVoucherRequest**

Create: `app/Http/Requests/Billing/ApplyVoucherRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Models\Subscription;
use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApplyVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subscription = Subscription::find($this->route('id'));
        return $subscription && $this->user()?->can('update', $subscription) ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'exists:vouchers,code'],
        ];
    }

    public function getSubscription(): Subscription
    {
        return Subscription::findOrFail($this->route('id'));
    }

    public function getVoucher(): Voucher
    {
        return Voucher::where('code', strtoupper($this->code))->firstOrFail();
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
```

- [ ] **Step 9: Commit**

```bash
git add app/Http/Requests/Billing/
git commit -m "feat: add billing Form Requests

- Create requests for all subscription operations
- Update, Cancel, Pause, Resume, Upgrade, Downgrade, Renew, ApplyVoucher
- Include authorization logic in each request
- Add helper methods for model retrieval"
```

### Task 13: Create SubscriptionResource

**Files:**
- Create: `app/Http/Resources/Billing/SubscriptionResource.php`

- [ ] **Step 1: Write the test**

Create: `tests/Unit/Resources/SubscriptionResourceTest.php`

```php
<?php

declare(strict_types=1);

use App\Http\Resources\Billing\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    tenancy()->initialize($this->tenant);
});

it('transforms subscription to array', function () {
    $plan = Plan::factory()->create(['name' => 'Pro Plan', 'price_monthly' => 29.99]);
    $user = User::factory()->for($this->tenant)->create();
    $subscription = Subscription::factory()
        ->for($user)
        ->for($plan)
        ->active()
        ->create([
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

    $resource = SubscriptionResource::make($subscription);
    $array = $resource->toArray(request());

    expect($array['id'])->toBe($subscription->id)
        ->and($array['status'])->toBe('active')
        ->and($array['billing_cycle'])->toBe('monthly')
        ->and($array['is_active'])->toBeTrue()
        ->and($array['is_trialing'])->toBeFalse()
        ->and($array['is_paused'])->toBeFalse()
        ->and($array['is_cancelled'])->toBeFalse()
        ->and($array['plan']['name'])->toBe('Pro Plan')
        ->and($array['plan']['price_monthly'])->toBe(29.99);
});

it('includes computed attributes', function () {
    $plan = Plan::factory()->create();
    $user = User::factory()->for($this->tenant)->create();
    $subscription = Subscription::factory()
        ->for($user)
        ->for($plan)
        ->active()
        ->create([
            'starts_at' => now()->subDays(15),
            'ends_at' => now()->addDays(15),
        ]);

    $resource = SubscriptionResource::make($subscription);
    $array = $resource->toArray(request());

    expect($array['days_remaining'])->toBeGreaterThanOrEqual(14)
        ->and($array['days_remaining'])->toBeLessThanOrEqual(16);
});

it('includes capability flags', function () {
    $plan = Plan::factory()->create();
    $user = User::factory()->for($this->tenant)->create();
    $subscription = Subscription::factory()
        ->for($user)
        ->for($plan)
        ->active()
        ->create();

    $resource = SubscriptionResource::make($subscription);
    $array = $resource->toArray(request());

    expect($array['can_pause'])->toBeTrue()
        ->and($array['can_cancel'])->toBeTrue()
        ->and($array['can_upgrade'])->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SubscriptionResourceTest`

Expected: FAIL - Resource does not exist

- [ ] **Step 3: Create SubscriptionResource**

Create: `app/Http/Resources/Billing/SubscriptionResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Billing;

use App\Http\Resources\Plan\PlanResource;
use App\Http\Resources\User\UserResource;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'billing_cycle' => $this->billing_cycle,
            'current_period_start' => $this->starts_at?->toIso8601String(),
            'current_period_end' => $this->ends_at?->toIso8601String(),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'grace_period_ends_at' => $this->grace_period_ends_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'is_active' => $this->isActive(),
            'is_trialing' => $this->isTrialing(),
            'is_paused' => $this->isPaused(),
            'is_cancelled' => $this->isCancelled(),
            'can_pause' => $this->canPause(),
            'can_cancel' => $this->canCancel(),
            'can_upgrade' => $this->canUpgrade(),
            'can_downgrade' => $this->canDowngrade(),
            'days_remaining' => $this->daysRemaining(),
            'plan' => PlanResource::make($this->whenLoaded('plan')),
            'user' => UserResource::make($this->whenLoaded('user')),
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active' 
            && $this->ends_at?->isFuture() === true;
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing' 
            || ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' || $this->cancelled_at !== null;
    }

    public function canPause(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['active', 'trialing', 'paused']);
    }

    public function canUpgrade(): bool
    {
        return $this->status === 'active';
    }

    public function canDowngrade(): bool
    {
        return $this->status === 'active';
    }

    public function daysRemaining(): int
    {
        if (! $this->ends_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->ends_at, false));
    }
}
```

- [ ] **Step 4: Create PlanResource (dependency)**

Create: `app/Http/Resources/Plan/PlanResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Plan;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price_monthly' => (float) $this->price_monthly,
            'price_yearly' => (float) ($this->price_yearly ?? 0),
            'price_quarterly' => (float) ($this->price_quarterly ?? 0),
            'currency_code' => $this->currency_code,
            'trial_days' => $this->trial_days ?? 0,
            'max_users' => $this->max_users,
            'max_storage_gb' => $this->max_storage_gb,
            'is_active' => (bool) $this->is_active,
            'is_popular' => (bool) ($this->is_popular ?? false),
            'features' => $this->whenLoaded('features'),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 5: Create UserResource (dependency)**

Create: `app/Http/Resources/User/UserResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar_url,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=SubscriptionResourceTest`

Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Resources/Billing/SubscriptionResource.php app/Http/Resources/Plan/PlanResource.php app/Http/Resources/User/UserResource.php tests/Unit/Resources/SubscriptionResourceTest.php
git commit -m "feat: add SubscriptionResource with computed attributes

- Transform subscription to API response
- Include computed attributes (is_active, days_remaining, etc.)
- Include capability flags (can_pause, can_cancel, etc.)
- Add PlanResource and UserResource as dependencies
- Add unit tests for resource transformation"
```

### Task 14: Refactor SubscriptionController

**Files:**
- Modify: `app/Http/Controllers/Billing/SubscriptionController.php`

- [ ] **Step 1: Update SubscriptionController**

Modify: `app/Http/Controllers/Billing/SubscriptionController.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\ApplyVoucherRequest;
use App\Http\Requests\Billing\CancelSubscriptionRequest;
use App\Http\Requests\Billing\CreateSubscriptionRequest;
use App\Http\Requests\Billing\DowngradeSubscriptionRequest;
use App\Http\Requests\Billing\PauseSubscriptionRequest;
use App\Http\Requests\Billing\ResumeSubscriptionRequest;
use App\Http\Requests\Billing\UpgradeSubscriptionRequest;
use App\Http\Resources\Billing\SubscriptionResource;
use App\Http\Resources\JsonResourceCollection;
use App\Services\Contracts\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionServiceInterface $subscriptionService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $subscription = $this->subscriptionService->getActiveSubscription($request->user());

        if (! $subscription) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => null,
                    'plan' => null,
                    'status' => 'inactive',
                    'current_period_start' => null,
                    'current_period_end' => null,
                ],
            ]);
        }

        return response()->json(
            JsonResourceCollection::single(SubscriptionResource::make($subscription))
        );
    }

    public function index(Request $request): JsonResponse
    {
        $subscriptions = $this->subscriptionService->getForTenant(
            tenancy()->tenant->id,
            $request->input('status'),
            (int) $request->input('per_page', 20)
        );

        return response()->json(
            JsonResourceCollection::paginated($subscriptions, SubscriptionResource::class)
        );
    }

    public function store(CreateSubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->create(
            $request->user(),
            $request->getPlan(),
            $request->payment_method,
            $request->payment_token,
            $request->billing_cycle
        );

        return response()->json(
            JsonResourceCollection::success(
                'Subscription created successfully',
                SubscriptionResource::make($subscription)->resolve()
            ),
            201
        );
    }

    public function upgrade(UpgradeSubscriptionRequest $request, string $id): JsonResponse
    {
        $subscription = $this->subscriptionService->upgrade(
            $request->getSubscription(),
            $request->getNewPlan()
        );

        return response()->json(
            JsonResourceCollection::success(
                'Subscription upgraded successfully',
                SubscriptionResource::make($subscription)->resolve()
            )
        );
    }

    public function downgrade(DowngradeSubscriptionRequest $request, string $id): JsonResponse
    {
        $subscription = $this->subscriptionService->downgrade(
            $request->getSubscription(),
            $request->getNewPlan()
        );

        return response()->json(
            JsonResourceCollection::success(
                'Subscription downgrade scheduled',
                SubscriptionResource::make($subscription)->resolve()
            )
        );
    }

    public function pause(PauseSubscriptionRequest $request, string $id): JsonResponse
    {
        $subscription = $this->subscriptionService->pause(
            $request->getSubscription()
        );

        return response()->json(
            JsonResourceCollection::success(
                'Subscription paused successfully',
                SubscriptionResource::make($subscription)->resolve()
            )
        );
    }

    public function resume(ResumeSubscriptionRequest $request, string $id): JsonResponse
    {
        $subscription = $this->subscriptionService->resume(
            $request->getSubscription()
        );

        return response()->json(
            JsonResourceCollection::success(
                'Subscription resumed successfully',
                SubscriptionResource::make($subscription)->resolve()
            )
        );
    }

    public function cancel(CancelSubscriptionRequest $request, string $id): JsonResponse
    {
        $subscription = $this->subscriptionService->cancel(
            $request->getSubscription(),
            $request->getReason()
        );

        return response()->json(
            JsonResourceCollection::success(
                'Subscription cancelled successfully',
                SubscriptionResource::make($subscription)->resolve()
            )
        );
    }

    public function renew(string $id): JsonResponse
    {
        $subscription = $this->subscriptionService->renew(
            Subscription::findOrFail($id)
        );

        return response()->json(
            JsonResourceCollection::success(
                'Subscription renewed successfully',
                SubscriptionResource::make($subscription)->resolve()
            )
        );
    }

    public function applyVoucher(ApplyVoucherRequest $request, string $id): JsonResponse
    {
        $this->subscriptionService->applyVoucher(
            $request->getSubscription(),
            $request->user(),
            $request->code
        );

        return response()->json(
            JsonResourceCollection::success('Voucher applied successfully')
        );
    }
}
```

Add missing import at top:

```php
use App\Models\Subscription;
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/Billing/SubscriptionController.php
git commit -m "refactor: refactor SubscriptionController to thin layer

- Remove all business logic from controller
- Use FormRequests for validation
- Inject SubscriptionServiceInterface
- Use API Resources for responses
- Controller now only handles HTTP concerns"
```

### Task 15: Update Subscription Tests to Work with New Architecture

**Files:**
- Modify: `tests/Feature/Billing/SubscriptionTest.php`

- [ ] **Step 1: Update existing tests**

Modify: `tests/Feature/Billing/SubscriptionTest.php`

```php
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
    $user = actingAs(User::factory()->create());
    $plan = Plan::factory()->create();

    $user->post('/api/billing/subscriptions', [
        'plan_id' => $plan->id,
        'payment_method' => 'card',
        'billing_cycle' => 'monthly',
    ])
        ->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription created successfully',
        ])
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
    $user = actingAs(User::factory()->create());

    $user->get('/api/billing/subscriptions')
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'inactive',
            ],
        ]);
});

it('can upgrade subscription', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()
        ->for($user)
        ->for(Plan::factory()->create(['price_monthly' => 29.99]))
        ->active()
        ->create();
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
    $subscription = Subscription::factory()
        ->for($user)
        ->for(Plan::factory())
        ->active()
        ->create();

    actingAs($user)
        ->post("/api/billing/subscriptions/{$subscription->id}/cancel", [
            'reason' => 'Too expensive',
        ])
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
        ]);
});

it('can pause subscription', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()
        ->for($user)
        ->for(Plan::factory())
        ->active()
        ->create();

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
    $subscription = Subscription::factory()
        ->for($user)
        ->for(Plan::factory())
        ->create(['status' => 'paused']);

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
```

- [ ] **Step 2: Run tests to verify they pass**

Run: `php artisan test --filter=SubscriptionTest`

Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Billing/SubscriptionTest.php
git commit -m "test: update subscription tests for new architecture

- Update tests to work with refactored controller
- Verify API response structure with new format
- Ensure all subscription operations work correctly"
```

---

## Phase 3: Continue with Other Modules

The pattern established above should be repeated for all remaining modules. The tasks below follow the same structure:

### Task 16: Create Notification Repository

- [ ] Create NotificationRepositoryInterface
- [ ] Create NotificationRepository with tests
- [ ] Register binding in AppServiceProvider

### Task 17: Create Notification Service Interface and Refactor Service

- [ ] Create NotificationServiceInterface
- [ ] Refactor NotificationService to use repositories
- [ ] Add comprehensive tests

### Task 18: Create Notification Form Requests

- [ ] CreateNotificationRequest
- [ ] UpdateNotificationRequest
- [ ] BulkSendNotificationRequest
- [ ] MarkNotificationReadRequest
- [ ] MarkAllNotificationsReadRequest
- [ ] UpdateNotificationPreferenceRequest

### Task 19: Create Notification Resources

- [ ] NotificationResource
- [ ] NotificationPreferenceResource

### Task 20: Refactor NotificationController

- [ ] Update to use FormRequests, Service, and Resources
- [ ] Update tests

### Task 21: Create Voucher Repository and Service

- [ ] VoucherRepositoryInterface and implementation
- [ ] VoucherServiceInterface and refactor service
- [ ] Add tests

### Task 22: Create Voucher Form Requests

- [ ] CreateVoucherRequest
- [ ] UpdateVoucherRequest
- [ ] GenerateVoucherRequest
- [ ] ValidateVoucherRequest
- [ ] BulkGenerateVoucherRequest

### Task 23: Create Voucher Resources

- [ ] VoucherResource

### Task 24: Refactor VoucherController

- [ ] Update to use new architecture
- [ ] Update tests

### Task 25-50: Repeat Pattern for Remaining Modules

Follow the same pattern for:
- Billing (Payment, Invoice, Refund, Transaction)
- Admin (Plan, TaxRate, FeatureFlag, Currency, ExchangeRate)
- Settings (Profile, Security)
- Usage (UsageMetric, UsagePricing, UsageAlert)
- Report (CustomReport, ReportTemplate, ScheduledReport)
- Export (ExportJob)
- Import (ImportJob)
- GDPR (GdprDeletionRequest)
- Analytics
- Audit (AuditLog)
- Webhook

---

## Phase 4: Performance Optimization

### Task 51: Add Database Indexes

- [ ] Add indexes to subscriptions table
- [ ] Add indexes to notifications table
- [ ] Add indexes to invoices table
- [ ] Add indexes to payments table
- [ ] Add indexes to other frequently queried tables

Create migration: `database/migrations/xxxx_xx_xx_add_performance_indexes.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('ends_at');
            $table->index('created_at');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['tenant_id', 'status']);
            $table->index(['user_id', 'read_at']);
            $table->index('created_at');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['tenant_id', 'status']);
            $table->index('subscription_id');
            $table->index('due_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['tenant_id', 'status']);
            $table->index('invoice_id');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex('ends_at');
            $table->dropIndex('created_at');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex(['user_id', 'read_at']);
            $table->dropIndex('created_at');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex('subscription_id');
            $table->dropIndex('due_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex('invoice_id');
            $table->dropIndex('transaction_id');
        });
    }
};
```

### Task 52: Implement Query Caching in Repositories

- [ ] Add caching to frequently accessed queries
- [ ] Use existing QueryCacheService

### Task 53: Queue Long-Running Operations

- [ ] Create job classes for export/import
- [ ] Create job for subscription renewal
- [ ] Configure queue worker

---

## Phase 5: Security Hardening

### Task 54: Create Policies

- [ ] SubscriptionPolicy
- [ ] NotificationPolicy
- [ ] VoucherPolicy
- [ ] InvoicePolicy
- [ ] PaymentPolicy

### Task 55: Add Rate Limiting to Routes

- [ ] Apply rate limiting to sensitive endpoints
- [ ] Stricter limits for payment operations

### Task 56: Add Secure Headers Middleware

- [ ] Create SetSecureHeaders middleware
- [ ] Register in Kernel

---

## Phase 6: Final Testing and Verification

### Task 57: Run Full Test Suite

- [ ] Run all unit tests
- [ ] Run all feature tests
- [ ] Fix any failing tests

### Task 58: Code Quality Check

- [ ] Run Pint on all PHP files
- - Run: `vendor/bin/pint --format agent`

### Task 59: Documentation Updates

- [ ] Update API documentation
- [ ] Update README with new architecture

### Task 60: Final Commit and Push

- [ ] Commit all changes
- [ ] Push to remote

---

## Summary

This plan implements Clean Architecture across the entire SaaS Tenancy Starter backend:

**Key Changes:**
- 60+ Form Requests created (one per action)
- 20+ Repository interfaces and implementations
- 18+ Services refactored to use repositories
- 30+ API Resources created
- 8+ Domain exception classes created
- 28 Controllers refactored to thin layers

**Testing:**
- TDD approach throughout
- Comprehensive unit and feature tests
- Tests updated to match new architecture

**Performance:**
- Database indexes added
- Query caching implemented
- Queue-based processing for long operations

**Security:**
- Policies for authorization
- Rate limiting on sensitive endpoints
- Secure headers middleware

**Estimated Tasks:** 60+
**Estimated Time:** Several days of focused development
