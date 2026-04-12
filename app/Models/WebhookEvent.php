<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        "webhook_id",
        "event_name",
        "payload",
        "status_code",
        "response",
        "retry_count",
        "delivered_at",
    ];

    protected $casts = [
        "payload" => "array",
        "response" => "array",
        "delivered_at" => "datetime",
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}

