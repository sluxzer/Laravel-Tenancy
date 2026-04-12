<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Activity Service
 *
 * Handles activity logging and tracking.
 */
class ActivityService
{
    /**
     * Log an activity.
     */
    public function logActivity(array $data): Activity
    {
        return Activity::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'description' => $data['description'],
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'metadata' => $data['metadata'] ?? [],
            'created_at' => Carbon::now()->toDateTimeString(),
        ]);
    }

    /**
     * Get activities for user.
     */
    public function getUserActivities(User $user, int $limit = 50): Collection
    {
        return Activity::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities for tenant.
     */
    public function getTenantActivities(Tenant $tenant, int $limit = 100): Collection
    {
        return Activity::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search activities.
     */
    public function searchActivities(Tenant $tenant, array $filters): Collection
    {
        $query = Activity::where('tenant_id', $tenant->id);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
