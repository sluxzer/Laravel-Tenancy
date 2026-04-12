<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class GdprDeletionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "user_id",
        "type",
        "requested_entities",
        "reason",
        "status",
        "processed_by",
        "processed_at",
        "admin_notes",
    ];

    protected $casts = [
        "requested_entities" => "array",
        "status" => "string",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

