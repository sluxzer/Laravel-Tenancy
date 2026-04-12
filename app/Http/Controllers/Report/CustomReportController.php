<?php

declare(strict_types=1);

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\CustomReport;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Custom Report Controller (Tenant)
 *
 * Tenant-level custom report management.
 */
class CustomReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get all custom reports for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = CustomReport::where('tenant_id', $tenant->id);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $reports = $query->with(['createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'pagination' => [
                'total' => $reports->total(),
                'per_page' => $reports->perPage(),
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific custom report.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $report = CustomReport::where('tenant_id', $tenant->id)
            ->with(['createdBy', 'template'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Create a new custom report.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|max:255',
            'query' => 'required|string',
            'parameters' => 'nullable|array',
            'schedule' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $report = $this->reportService->createCustomReport(
            $tenant,
            $validated['name'],
            $validated['type'],
            $validated['query'],
            $validated['description'] ?? null,
            $validated['parameters'] ?? [],
            $validated['schedule'] ?? null,
            $validated['is_active'] ?? true,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Custom report created successfully',
            'data' => $report,
        ], 201);
    }

    /**
     * Update a custom report.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $report = CustomReport::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|string|max:255',
            'query' => 'sometimes|string',
            'parameters' => 'nullable|array',
            'schedule' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $report->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Custom report updated successfully',
            'data' => $report,
        ]);
    }

    /**
     * Delete a custom report.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $report = CustomReport::where('tenant_id', $tenant->id)->findOrFail($id);
        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Custom report deleted successfully',
        ]);
    }

    /**
     * Run a custom report.
     */
    public function run(string $id, Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $report = CustomReport::where('tenant_id', $tenant->id)->findOrFail($id);

        $parameters = $request->input('parameters', []);

        $result = $this->reportService->runCustomReport($report, $parameters);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Schedule a report run.
     */
    public function schedule(string $id, Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $report = CustomReport::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'frequency' => 'required|in:daily,weekly,monthly,custom',
            'cron_expression' => 'nullable|string',
            'run_at' => 'nullable|date',
            'notify_email' => 'nullable|email',
        ]);

        $report->update([
            'schedule' => $validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report scheduled successfully',
            'data' => $report,
        ]);
    }

    /**
     * Duplicate a report.
     */
    public function duplicate(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $original = CustomReport::where('tenant_id', $tenant->id)->findOrFail($id);

        $duplicate = $original->replicate();
        $duplicate->name = $duplicate->name.' (Copy)';
        $duplicate->created_by = $request->user()->id;
        $duplicate->save();

        return response()->json([
            'success' => true,
            'message' => 'Report duplicated successfully',
            'data' => $duplicate,
        ]);
    }
}
