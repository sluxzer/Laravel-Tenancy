<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\WithEagerLoading;
use App\Traits\WithQueryCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Concerns\MaintenanceMode;
use Stancl\Tenancy\Database\Contracts\TenantWithDatabase;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, MaintenanceMode, HasFactory, WithEagerLoading, WithQueryCache;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    public $fillable = [
        'id',
        'name',
        'email',
        'plan_id',
        'currency_id',
        'settings',
        'data',
        'rate_limit_per_minute',
        'rate_limit_per_hour',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'data' => 'array',
        ];
    }

    /**
     * Get users for the tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get invoices for the tenant.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get tax settings for the tenant.
     */
    public function taxSettings(): HasMany
    {
        return $this->hasMany(TenantTaxSetting::class);
    }

    /**
     * Get manual payments for the tenant.
     */
    public function manualPayments(): HasMany
    {
        return $this->hasMany(ManualPayment::class);
    }

    /**
     * Get subscriptions for the tenant.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the currency for the tenant.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get usage metrics for the tenant.
     */
    public function usageMetrics(): HasMany
    {
        return $this->hasMany(UsageMetric::class);
    }

    /**
     * Get usage alerts for the tenant.
     */
    public function usageAlerts(): HasMany
    {
        return $this->hasMany(UsageAlert::class);
    }

    /**
     * Get the tenant's key name.
     */
    public function getTenantKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Get the tenant's key.
     */
    public function getTenantKey()
    {
        return $this->getKey();
    }

    /**
     * Get value of an internal key.
     */
    public function getInternal(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Set value of an internal key.
     */
    public function setInternal(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Run a callback in this tenant's environment.
     */
    public function run(callable $callback)
    {
        return tenancy()->run($this, $callback);
    }
}
