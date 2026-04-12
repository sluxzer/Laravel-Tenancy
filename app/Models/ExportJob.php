<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class ExportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "user_id",
        "type",
        "filters",
        "selected_fields",
        "status",
        "total_records",
        "started_at",
        "completed_at",
        "file_path",
        "error_message",
    ];

    protected $casts = [
        "filters" => "array",
        "selected_fields" => "array",
        "total_records" => "integer",
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
}

