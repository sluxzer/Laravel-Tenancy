<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class AnalyticsEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "user_id",
        "event_name",
        "properties",
        "occurred_at",
    ];

    protected $casts = [
        "properties" => "array",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

