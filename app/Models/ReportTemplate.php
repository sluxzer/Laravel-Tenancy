<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "name",
        "description",
        "type",
        "query",
        "columns",
        "is_system",
        "is_enabled",
    ];

    protected $casts = [
        "columns" => "array",
        "is_system" => "boolean",
        "is_enabled" => "boolean",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customReports(): HasMany
    {
        return $this->hasMany(CustomReport::class);
    }

    public function reportRuns(): HasMany
    {
        return $this->hasMany(ReportRun::class);
    }
}

