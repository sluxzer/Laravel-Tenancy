<?php

declare(strict_types=1);

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\ReportRun;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Report Run Controller (Tenant)
 *
 * Tenant-level report run history management.
 */
class ReportRunController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get all report runs for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = ReportRun::where('tenant_id', $tenant->id);

        if ($request->has('report_id')) {
            $query->where('custom_report_id', $request->input('report_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $runs = $query->with(['report', 'runBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $runs->items(),
            'pagination' => [
                'total' => $runs->total(),
                'per_page' => $runs->perPage(),
                'current_page' => $runs->currentPage(),
                'last_page' => $runs->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific report run.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $run = ReportRun::where('tenant_id', $tenant->id)
            ->with(['report', 'runBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $run,
        ]);
    }

    /**
     * Run a report manually.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'report_id' => 'required|exists:custom_reports,id',
            'parameters' => 'nullable|array',
        ]);

        $run = $this->reportService->runReport(
            $tenant,
            $validated['report_id'],
            $validated['parameters'] ?? [],
            'manual',
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Report run started successfully',
            'data' => $run,
        ], 201);
    }

    /**
     * Get report run results.
     */
    public function results(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $run = ReportRun::where('tenant_id', $tenant->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'run_id' => $run->id,
                'results' => $run->results,
                'row_count' => $run->row_count,
                'status' => $run->status,
            ],
        ]);
    }

    /**
     * Download report run results.
     */
    public function download(string $id, Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'format' => 'required|in:json,csv,excel',
        ]);

        $run = ReportRun::where('tenant_id', $tenant->id)->findOrFail($id);

        $result = $this->reportService->exportReportRun($run, $validated['format']);

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $result['url'],
                'format' => $validated['format'],
                'file_name' => $result['file_name'],
            ],
        ]);
    }

    /**
     * Cancel a running report.
     */
    public function cancel(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $run = ReportRun::where('tenant_id', $tenant->id)
            ->where('status', 'running')
            ->findOrFail($id);

        $run->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Report run cancelled successfully',
            'data' => $run,
        ]);
    }

    /**
     * Delete a report run.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $run = ReportRun::where('tenant_id', $tenant->id)->findOrFail($id);
        $run->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report run deleted successfully',
        ]);
    }

    /**
     * Get report run statistics.
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

        $stats = ReportRun::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                status,
                COUNT(*) as count,
                SUM(row_count) as total_rows,
                AVG(CASE WHEN status = "completed" THEN row_count END) as avg_rows
            ')
            ->groupBy('status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'total_runs' => $stats->sum('count'),
                'total_rows' => $stats->sum('total_rows'),
                'period' => [
                    'start' => $startDate->toIso8601String(),
                    'end' => $endDate->toIso8601String(),
                ],
            ],
        ]);
    }
}
