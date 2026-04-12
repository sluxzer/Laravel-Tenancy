<?php

declare(strict_types=1);

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\ScheduledReport;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Scheduled Report Controller (Tenant)
 *
 * Tenant-level scheduled report management.
 */
class ScheduledReportController extends Controller
{
    /**
     * Get all scheduled reports for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = ScheduledReport::where('tenant_id', $tenant->id);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('frequency')) {
            $query->where('frequency', $request->input('frequency'));
        }

        $reports = $query->with(['customReport', 'createdBy'])
            ->orderBy('next_run_at', 'asc')
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
     * Get a specific scheduled report.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $report = ScheduledReport::where('tenant_id', $tenant->id)
            ->with(['customReport', 'createdBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Create a new scheduled report.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'custom_report_id' => 'required|exists:custom_reports,id',
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly,custom',
            'cron_expression' => 'nullable|string',
            'next_run_at' => 'required|date',
            'notify_email' => 'nullable|email',
            'notify_slack' => 'nullable|boolean',
            'is_active' => 'boolean',
        ]);

        // Calculate next run for non-custom frequencies
        if ($validated['frequency'] !== 'custom') {
            $nextRun = match ($validated['frequency']) {
                'daily' => now()->addDay(),
                'weekly' => now()->addWeek(),
                'monthly' => now()->addMonth(),
            };
            $validated['next_run_at'] = $nextRun;
        }

        $report = ScheduledReport::create([
            'tenant_id' => $tenant->id,
            'custom_report_id' => $validated['custom_report_id'],
            'name' => $validated['name'],
            'frequency' => $validated['frequency'],
            'cron_expression' => $validated['cron_expression'] ?? null,
            'next_run_at' => $validated['next_run_at'],
            'notify_email' => $validated['notify_email'] ?? null,
            'notify_slack' => $validated['notify_slack'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Scheduled report created successfully',
            'data' => $report,
        ], 201);
    }

    /**
     * Update a scheduled report.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $report = ScheduledReport::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'frequency' => 'sometimes|in:daily,weekly,monthly,custom',
            'cron_expression' => 'nullable|string',
            'next_run_at' => 'sometimes|date',
            'notify_email' => 'nullable|email',
            'notify_slack' => 'nullable|boolean',
            'is_active' => 'boolean',
        ]);

        $report->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Scheduled report updated successfully',
            'data' => $report,
        ]);
    }

    /**
     * Delete a scheduled report.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $report = ScheduledReport::where('tenant_id', $tenant->id)->findOrFail($id);
        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Scheduled report deleted successfully',
        ]);
    }

    /**
     * Pause a scheduled report.
     */
    public function pause(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $report = ScheduledReport::where('tenant_id', $tenant->id)->findOrFail($id);
        $report->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Scheduled report paused successfully',
            'data' => $report,
        ]);
    }

    /**
     * Resume a paused scheduled report.
     */
    public function resume(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $report = ScheduledReport::where('tenant_id', $tenant->id)->findOrFail($id);

        // Update next run if needed
        if ($report->next_run_at < now()) {
            $nextRun = match ($report->frequency) {
                'daily' => now()->addDay(),
                'weekly' => now()->addWeek(),
                'monthly' => now()->addMonth(),
                default => now()->addDay(),
            };
            $report->update([
                'is_active' => true,
                'next_run_at' => $nextRun,
            ]);
        } else {
            $report->update(['is_active' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Scheduled report resumed successfully',
            'data' => $report,
        ]);
    }

    /**
     * Run a scheduled report now.
     */
    public function runNow(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $report = ScheduledReport::where('tenant_id', $tenant->id)->findOrFail($id);

        // Dispatch job to run the report
        dispatch(function () use ($report) {
            $reportService = app(ReportService::class);
            $reportService->runReport(
                $report->tenant,
                $report->custom_report_id,
                [],
                'scheduled',
                $report->created_by
            );

            // Update next run
            $nextRun = match ($report->frequency) {
                'daily' => now()->addDay(),
                'weekly' => now()->addWeek(),
                'monthly' => now()->addMonth(),
                default => now()->addDay(),
            };
            $report->update(['next_run_at' => $nextRun]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Scheduled report queued for execution',
        ]);
    }
}
