<?php

declare(strict_types=1);

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\ExportJob;
use App\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Export Controller (Tenant)
 *
 * Tenant-level data export management.
 */
class ExportController extends Controller
{
    /**
     * Get all export jobs for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = ExportJob::where('tenant_id', $tenant->id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $jobs = $query->with(['requestedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'pagination' => [
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific export job.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $job = ExportJob::where('tenant_id', $tenant->id)
            ->with(['requestedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    }

    /**
     * Create a new export job.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'type' => 'required|in:users,invoices,subscriptions,activities,analytics,custom',
            'format' => 'required|in:json,csv,excel',
            'filters' => 'nullable|array',
            'tables' => 'nullable|array',
            'tables.*' => 'string',
            'description' => 'nullable|string',
        ]);

        $job = ExportJob::create([
            'tenant_id' => $tenant->id,
            'type' => $validated['type'],
            'format' => $validated['format'],
            'filters' => $validated['filters'] ?? [],
            'tables' => $validated['tables'] ?? [],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'requested_by' => $request->user()->id,
        ]);

        // Dispatch job to process export
        dispatch(function () use ($job) {
            $exportService = app(ExportService::class);
            $exportService->processExportJob($job);
        });

        return response()->json([
            'success' => true,
            'message' => 'Export job created successfully',
            'data' => $job,
        ], 201);
    }

    /**
     * Download export file.
     */
    public function download(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $job = ExportJob::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($job->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Export job has not completed yet',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $job->file_path,
                'file_name' => $job->file_name,
                'format' => $job->format,
                'size' => $job->file_size,
            ],
        ]);
    }

    /**
     * Cancel an export job.
     */
    public function cancel(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $job = ExportJob::where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'processing'])
            ->findOrFail($id);

        $job->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Export job cancelled successfully',
            'data' => $job,
        ]);
    }

    /**
     * Delete an export job.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $job = ExportJob::where('tenant_id', $tenant->id)->findOrFail($id);

        // Delete file if it exists
        if ($job->file_path && \Storage::exists($job->file_path)) {
            \Storage::delete($job->file_path);
        }

        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Export job deleted successfully',
        ]);
    }

    /**
     * Get export job status.
     */
    public function status(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $job = ExportJob::where('tenant_id', $tenant->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $job->status,
                'progress' => $job->progress,
                'completed_at' => $job->completed_at,
                'error_message' => $job->error_message,
            ],
        ]);
    }

    /**
     * Get export job statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now()->startOfMonth();
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now();

        $stats = ExportJob::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                status,
                COUNT(*) as count,
                SUM(file_size) as total_size
            ')
            ->groupBy('status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'total_jobs' => $stats->sum('count'),
                'total_size' => $stats->sum('total_size'),
                'period' => [
                    'start' => $startDate->toIso8601String(),
                    'end' => $endDate->toIso8601String(),
                ],
            ],
        ]);
    }
}
