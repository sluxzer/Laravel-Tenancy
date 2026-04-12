<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        "from_currency_id",
        "to_currency_id",
        "rate",
        "fetched_at",
    ];

    protected $casts = [
        "fetched_at" => "datetime",
    ];

    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, "from_currency_id");
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, "to_currency_id");
    }

    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }
}

