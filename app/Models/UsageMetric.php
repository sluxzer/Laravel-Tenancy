<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class UsageMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "metric_name",
        "value",
        "recorded_at",
    ];

    protected $casts = [
        "recorded_at" => "datetime",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

