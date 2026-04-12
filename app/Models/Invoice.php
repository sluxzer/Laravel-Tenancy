<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\CarbonImmutable;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        "number",
        "tenant_id",
        "user_id",
        "subscription_id",
        "subtotal",
        "tax_amount",
        "discount_amount",
        "total_amount",
        "currency",
        "status",
        "due_date",
        "paid_at",
        "cancelled_at",
        "notes",
    ];

    protected $casts = [
        "due_date" => "datetime",
        "paid_at" => "datetime",
        "cancelled_at" => "datetime",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}

