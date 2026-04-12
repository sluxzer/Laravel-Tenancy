<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\CarbonImmutable;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "user_id",
        "plan_id",
        "status",
        "starts_at",
        "ends_at",
        "trial_ends_at",
        "grace_period_ends_at",
        "cancelled_at",
        "metadata",
    ];

    protected $casts = [
        "starts_at" => "datetime",
        "ends_at" => "datetime",
        "trial_ends_at" => "datetime",
        "grace_period_ends_at" => "datetime",
        "cancelled_at" => "datetime",
        "metadata" => "array",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}

