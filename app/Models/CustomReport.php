<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\CarbonImmutable;

class CustomReport extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "user_id",
        "name",
        "description",
        "query",
        "parameters",
        "status",
        "report_template_id",
    ];

    protected $casts = [
        "parameters" => "array",
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

    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ReportRun::class);
    }
}

