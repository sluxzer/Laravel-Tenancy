<?php

declare(strict_types=1);

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Audit Log Controller (Tenant)
 *
 * Tenant-level audit log management.
 */
class AuditLogController extends Controller
{
    /**
     * Get all audit logs for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = AuditLog::where('tenant_id', $tenant->id);

        if ($request->has('action')) {
            $query->where('action', 'like', '%'.$request->input('action').'%');
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->input('start_date'),
                $request->input('end_date'),
            ]);
        }

        $auditLogs = $query->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $auditLogs->items(),
            'pagination' => [
                'total' => $auditLogs->total(),
                'per_page' => $auditLogs->perPage(),
                'current_page' => $auditLogs->currentPage(),
                'last_page' => $auditLogs->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific audit log.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $auditLog = AuditLog::where('tenant_id', $tenant->id)
            ->with(['user'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $auditLog,
        ]);
    }

    /**
     * Get audit log summary.
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

        $summary = AuditLog::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                action,
                COUNT(*) as count,
                MAX(created_at) as last_occurrence
            ')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'total_logs' => $summary->sum('count'),
                'period' => [
                    'start' => $startDate->toIso8601String(),
                    'end' => $endDate->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Get audit logs for a specific model.
     */
    public function forModel(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|string',
        ]);

        $auditLogs = AuditLog::where('tenant_id', $tenant->id)
            ->where('model_type', $validated['model_type'])
            ->where('model_id', $validated['model_id'])
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $auditLogs,
        ]);
    }

    /**
     * Get recent audit logs.
     */
    public function recent(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;
        $limit = min($request->input('limit', 20), 100);

        $auditLogs = AuditLog::where('tenant_id', $tenant->id)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $auditLogs,
        ]);
    }

    /**
     * Export audit logs.
     */
    public function export(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'format' => 'required|in:json,csv',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'action' => 'nullable|string',
        ]);

        $query = AuditLog::where('tenant_id', $tenant->id);

        if ($validated['action'] ?? null) {
            $query->where('action', 'like', '%'.$validated['action'].'%');
        }

        if ($validated['start_date'] ?? null) {
            $query->where('created_at', '>=', $validated['start_date']);
        }

        if ($validated['end_date'] ?? null) {
            $query->where('created_at', '<=', $validated['end_date']);
        }

        $auditLogs = $query->with(['user'])->get();

        if ($validated['format'] === 'csv') {
            $headers = ['ID', 'Action', 'Model Type', 'Model ID', 'User', 'Description', 'IP Address', 'Created At'];
            $rows = $auditLogs->map(fn ($log) => [
                $log->id,
                $log->action,
                $log->model_type ?? '',
                $log->model_id ?? '',
                $log->user?->name ?? 'System',
                $log->description ?? '',
                $log->ip_address ?? '',
                $log->created_at,
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
                'audit_logs' => $auditLogs,
            ],
        ]);
    }
}
