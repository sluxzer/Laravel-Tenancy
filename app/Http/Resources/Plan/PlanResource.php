<?php

declare(strict_types=1);

namespace App\Http\Resources\Plan;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Plan $resource
 */
class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'price_monthly' => (float) $this->resource->price_monthly,
            'price_yearly' => $this->resource->price_yearly ? (float) $this->resource->price_yearly : null,
            'price_quarterly' => null, // Not in current schema, placeholder for future
            'currency_code' => 'USD', // Default currency, can be made configurable
            'trial_days' => 0, // Not in current schema, placeholder for future
            'max_users' => $this->resource->max_users,
            'max_storage_gb' => $this->resource->max_storage_mb ? round($this->resource->max_storage_mb / 1024, 2) : null,
            'is_active' => (bool) $this->resource->is_active,
            'is_popular' => false, // Not in current schema, placeholder for future
            'features' => $this->whenLoaded('features', fn () => $this->resource->features ?? []),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
