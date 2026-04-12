<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsagePricing extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "metric_name",
        "name",
        "price_per_unit",
        "included_units",
        "pricing_tiers",
        "is_active",
    ];

    protected $casts = [
        "pricing_tiers" => "array",
        "is_active" => "boolean",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

