<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "user_id",
        "type",
        "title",
        "message",
        "data",
        "is_read",
        "sent_at",
    ];

    protected $casts = [
        "data" => "array",
        "is_read" => "boolean",
        "sent_at" => "datetime",
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

