<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantTaxSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'tax_rate_id',
        'tax_id',
        'is_tax_exempt',
        'company_name',
        'tax_number',
        'vat_number',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
    ];

    protected $casts = [
        'country' => 'string',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }
}
