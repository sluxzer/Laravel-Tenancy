<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "code",
        "name",
        "description",
        "type",
        "value",
        "plan_id",
        "max_uses",
        "used_count",
        "expires_at",
        "is_active",
    ];

    protected $casts = [
        "expires_at" => "datetime",
        "is_active" => "boolean",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}

