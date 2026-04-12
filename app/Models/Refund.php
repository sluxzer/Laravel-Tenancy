<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "user_id",
        "transaction_id",
        "invoice_id",
        "amount",
        "currency",
        "reason",
        "status",
        "processed_at",
        "admin_notes",
    ];

    protected $casts = [
        "processed_at" => "datetime",
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

