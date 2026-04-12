<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class ReportRun extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "user_id",
        "report_template_id",
        "custom_report_id",
        "name",
        "parameters",
        "status",
        "total_rows",
        "started_at",
        "completed_at",
        "file_path",
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

    public function customReport(): BelongsTo
    {
        return $this->belongsTo(CustomReport::class);
    }
}

