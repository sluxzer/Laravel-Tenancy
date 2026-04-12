<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\ActivityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Activity Controller (Tenant)
 *
 * Tenant-level activity logging management.
 */
class ActivityController extends Controller
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Get all activities for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = Activity::where('tenant_id', $tenant->id);

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->input('start_date'),
                $request->input('end_date'),
            ]);
        }

        $activities = $query->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $activities->items(),
            'pagination' => [
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific activity.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $activity = Activity::where('tenant_id', $tenant->id)
            ->with(['user'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $activity,
        ]);
    }

    /**
     * Log a new activity.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $activity = $this->activityService->log(
            $tenant,
            $request->user(),
            $validated['type'],
            $validated['description'] ?? null,
            $validated['metadata'] ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Activity logged successfully',
            'data' => $activity,
        ], 201);
    }

    /**
     * Get activity feed for user.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = tenancy()->tenant;

        $limit = $request->input('limit', 20);

        $activities = Activity::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Get activity summary.
     */
    public function summary(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->startOfDay();
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();

        $summary = Activity::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                type,
                COUNT(*) as count,
                MAX(created_at) as last_activity
            ')
            ->groupBy('type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'total_activities' => $summary->sum('count'),
                'period' => [
                    'start' => $startDate->toIso8601String(),
                    'end' => $endDate->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Get recent activities.
     */
    public function recent(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;
        $limit = min($request->input('limit', 10), 100);

        $activities = Activity::where('tenant_id', $tenant->id)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Get activities by type.
     */
    public function byType(Request $request, string $type): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->subDays(30);
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();

        $activities = Activity::where('tenant_id', $tenant->id)
            ->where('type', $type)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $activities->items(),
            'pagination' => [
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
            ],
        ]);
    }

    /**
     * Export activities.
     */
    public function export(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'format' => 'required|in:json,csv',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'type' => 'nullable|string',
        ]);

        $query = Activity::where('tenant_id', $tenant->id);

        if ($validated['type'] ?? null) {
            $query->where('type', $validated['type']);
        }

        if ($validated['start_date'] ?? null) {
            $query->where('created_at', '>=', $validated['start_date']);
        }

        if ($validated['end_date'] ?? null) {
            $query->where('created_at', '<=', $validated['end_date']);
        }

        $activities = $query->with(['user'])->get();

        if ($validated['format'] === 'csv') {
            // Generate CSV
            $headers = ['ID', 'Type', 'Description', 'User', 'IP Address', 'Created At'];
            $rows = $activities->map(fn ($a) => [
                $a->id,
                $a->type,
                $a->description ?? '',
                $a->user?->name ?? 'System',
                $a->ip_address ?? '',
                $a->created_at,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'format' => 'csv',
                    'headers' => $headers,
                    'rows' => $rows,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'format' => 'json',
                'activities' => $activities,
            ],
        ]);
    }
}
