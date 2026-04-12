<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class UsageAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "metric_name",
        "threshold_value",
        "comparison_operator",
        "type",
        "webhook_url",
        "is_active",
        "trigger_count",
        "last_triggered_at",
    ];

    protected $casts = [
        "last_triggered_at" => "datetime",
        "is_active" => "boolean",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

