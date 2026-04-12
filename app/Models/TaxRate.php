<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "name",
        "rate",
        "type",
        "country_code",
        "region_code",
        "is_default",
        "is_active",
        "description",
        "metadata",
    ];

    protected $casts = [
        "is_default" => "boolean",
        "is_active" => "boolean",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

