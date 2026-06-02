<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'currency_code',
        'min_amount',
        'max_discount',
        'plan_ids',
        'max_uses',
        'used_count',
        'valid_from',
        'valid_until',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'plan_ids' => 'array',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
