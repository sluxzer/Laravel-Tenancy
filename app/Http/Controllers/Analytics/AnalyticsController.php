<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Analytics Controller (Tenant)
 *
 * Tenant-level analytics event tracking.
 */
class AnalyticsController extends Controller
{
    /**
     * Track an analytics event.
     */
    public function track(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'event_name' => 'required|string|max:255',
            'properties' => 'nullable|array',
            'user_id' => 'nullable|exists:users,id',
        ]);

        AnalyticsEvent::create([
            'tenant_id' => $tenant->id,
            'user_id' => $validated['user_id'] ?? $request->user()?->id,
            'event_name' => $validated['event_name'],
            'properties' => $validated['properties'] ?? [],
            'url' => $request->header('Referer'),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Event tracked successfully',
        ]);
    }

    /**
     * Batch track analytics events.
     */
    public function batchTrack(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'events' => 'required|array',
            'events.*.event_name' => 'required|string|max:255',
            'events.*.properties' => 'nullable|array',
        ]);

        $events = collect($validated['events'])->map(function ($event) use ($tenant, $request) {
            return [
                'tenant_id' => $tenant->id,
                'user_id' => $event['user_id'] ?? $request->user()?->id,
                'event_name' => $event['event_name'],
                'properties' => $event['properties'] ?? [],
                'url' => $request->header('Referer'),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        AnalyticsEvent::insert($events);

        return response()->json([
            'success' => true,
            'message' => count($events).' events tracked successfully',
        ]);
    }

    /**
     * Get analytics events for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = AnalyticsEvent::where('tenant_id', $tenant->id);

        if ($request->has('event_name')) {
            $query->where('event_name', $request->input('event_name'));
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

        $events = $query->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'pagination' => [
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ],
        ]);
    }

    /**
     * Get analytics summary.
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

        $summary = AnalyticsEvent::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                event_name,
                COUNT(*) as count,
                COUNT(DISTINCT user_id) as unique_users,
                MIN(created_at) as first_occurrence,
                MAX(created_at) as last_occurrence
            ')
            ->groupBy('event_name')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'total_events' => $summary->sum('count'),
                'total_unique_users' => $summary->max('unique_users') ?? 0,
                'period' => [
                    'start' => $startDate->toIso8601String(),
                    'end' => $endDate->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Get events by name.
     */
    public function byName(Request $request, string $eventName): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->subDays(30);
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();

        $events = AnalyticsEvent::where('tenant_id', $tenant->id)
            ->where('event_name', $eventName)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'pagination' => [
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ],
        ]);
    }

    /**
     * Get unique event names.
     */
    public function eventNames(): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $eventNames = AnalyticsEvent::where('tenant_id', $tenant->id)
            ->select('event_name')
            ->distinct()
            ->orderBy('event_name')
            ->pluck('event_name');

        return response()->json([
            'success' => true,
            'data' => $eventNames,
        ]);
    }

    /**
     * Export analytics events.
     */
    public function export(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'format' => 'required|in:json,csv',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'event_name' => 'nullable|string',
        ]);

        $query = AnalyticsEvent::where('tenant_id', $tenant->id);

        if ($validated['event_name'] ?? null) {
            $query->where('event_name', $validated['event_name']);
        }

        if ($validated['start_date'] ?? null) {
            $query->where('created_at', '>=', $validated['start_date']);
        }

        if ($validated['end_date'] ?? null) {
            $query->where('created_at', '<=', $validated['end_date']);
        }

        $events = $query->with(['user'])->get();

        if ($validated['format'] === 'csv') {
            $headers = ['ID', 'Event Name', 'User', 'URL', 'IP Address', 'Created At'];
            $rows = $events->map(fn ($event) => [
                $event->id,
                $event->event_name,
                $event->user?->name ?? 'Anonymous',
                $event->url ?? '',
                $event->ip_address ?? '',
                $event->created_at,
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
                'events' => $events,
            ],
        ]);
    }
}
