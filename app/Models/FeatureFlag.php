<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'key',
        'description',
        'is_enabled',
        'is_public',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_public' => 'boolean',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
