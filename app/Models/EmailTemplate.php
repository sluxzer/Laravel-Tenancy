<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'slug',
        'name',
        'subject',
        'html_content',
        'text_content',
        'variables',
        'is_system',
        'is_enabled',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_system' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
