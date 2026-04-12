<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        "code",
        "name",
        "symbol",
        "is_default",
        "is_active",
    ];

    protected $casts = [
        "is_default" => "boolean",
        "is_active" => "boolean",
    ];

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, "from_currency_id");
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}

