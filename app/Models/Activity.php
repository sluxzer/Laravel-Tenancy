<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\CarbonImmutable;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "user_id",
        "type",
        "description",
        "ip_address",
        "user_agent",
        "metadata",
        "created_at",
    ];

    protected $casts = [
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
}

