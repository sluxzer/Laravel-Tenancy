<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "user_id",
        "action",
        "model_type",
        "model_id",
        "changes",
        "description",
        "ip_address",
        "user_agent",
        "created_at",
    ];

    protected $casts = [
        "changes" => "array",
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

