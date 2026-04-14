<?php

declare(strict_types=1);

namespace App\Http\Resources\Billing;

use App\Http\Resources\Plan\PlanResource;
use App\Http\Resources\User\UserResource;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Subscription $resource
 */
class SubscriptionResource extends JsonResource
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
            'status' => $this->resource->status,
            'billing_cycle' => null, // Not in current schema, placeholder for future
            'current_period_start' => $this->resource->starts_at?->toIso8601String(),
            'current_period_end' => $this->resource->ends_at?->toIso8601String(),
            'trial_ends_at' => $this->resource->trial_ends_at?->toIso8601String(),
            'grace_period_ends_at' => $this->resource->grace_period_ends_at?->toIso8601String(),
            'cancelled_at' => $this->resource->cancelled_at?->toIso8601String(),
            'cancellation_reason' => null, // Not in current schema, placeholder for future
            'metadata' => $this->resource->metadata ?? [],
            'is_active' => $this->isActive(),
            'is_trialing' => $this->isTrialing(),
            'is_paused' => $this->isPaused(),
            'is_cancelled' => $this->isCancelled(),
            'can_pause' => $this->canPause(),
            'can_cancel' => $this->canCancel(),
            'can_upgrade' => $this->canUpgrade(),
            'can_downgrade' => $this->canDowngrade(),
            'days_remaining' => $this->daysRemaining(),
            'plan' => $this->whenLoaded('plan', fn () => PlanResource::make($this->resource->plan)->resolve($request)),
            'user' => $this->whenLoaded('user', fn () => UserResource::make($this->resource->user)->resolve($request)),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Check if subscription is active.
     */
    private function isActive(): bool
    {
        return $this->resource->status === 'active'
            && $this->resource->ends_at
            && $this->resource->ends_at->isFuture();
    }

    /**
     * Check if subscription is in trial period.
     */
    private function isTrialing(): bool
    {
        return $this->resource->status === 'trialing'
            || ($this->resource->trial_ends_at && $this->resource->trial_ends_at->isFuture());
    }

    /**
     * Check if subscription is paused.
     */
    private function isPaused(): bool
    {
        return $this->resource->status === 'paused';
    }

    /**
     * Check if subscription is cancelled.
     */
    private function isCancelled(): bool
    {
        return $this->resource->status === 'cancelled'
            || $this->resource->cancelled_at !== null;
    }

    /**
     * Check if subscription can be paused.
     */
    private function canPause(): bool
    {
        return in_array($this->resource->status, ['active', 'trialing'], true);
    }

    /**
     * Check if subscription can be cancelled.
     */
    private function canCancel(): bool
    {
        return in_array($this->resource->status, ['active', 'trialing', 'paused'], true);
    }

    /**
     * Check if subscription can be upgraded.
     */
    private function canUpgrade(): bool
    {
        return $this->resource->status === 'active';
    }

    /**
     * Check if subscription can be downgraded.
     */
    private function canDowngrade(): bool
    {
        return $this->resource->status === 'active';
    }

    /**
     * Get days remaining in current billing period.
     */
    private function daysRemaining(): int
    {
        if (! $this->resource->ends_at) {
            return 0;
        }

        $days = now()->diffInDays($this->resource->ends_at, false);

        return (int) max(0, $days);
    }
}
