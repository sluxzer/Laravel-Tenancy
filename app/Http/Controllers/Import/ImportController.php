<?php

declare(strict_types=1);

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

/**
 * Import Controller (Tenant)
 *
 * Tenant-level data import management.
 */
class ImportController extends Controller
{
    /**
     * Validate import file.
     */
    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', File::types(['csv', 'xlsx', 'xls'])],
            'type' => 'required|in:users,invoices,subscriptions,activities,analytics,custom',
        ]);

        $file = $validated['file'];
        $path = $file->store('imports/temp', 'local');

        $importService = app(ImportService::class);
        $validation = $importService->validateImportFile($path, $validated['type']);

        // Clean up temp file
        Storage::delete($path);

        return response()->json([
            'success' => true,
            'data' => $validation,
        ]);
    }

    /**
     * Preview import data.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', File::types(['csv', 'xlsx', 'xls'])],
            'type' => 'required|in:users,invoices,subscriptions,activities,analytics,custom',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $file = $validated['file'];
        $path = $file->store('imports/temp', 'local');

        $importService = app(ImportService::class);
        $preview = $importService->previewImportFile(
            $path,
            $validated['type'],
            $validated['limit'] ?? 10
        );

        return response()->json([
            'success' => true,
            'data' => $preview,
        ]);
    }

    /**
     * Process import.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'file' => ['required', File::types(['csv', 'xlsx', 'xls'])],
            'type' => 'required|in:users,invoices,subscriptions,activities,analytics,custom',
            'mapping' => 'nullable|array',
            'skip_duplicates' => 'boolean',
            'on_error' => 'required|in:stop,skip,continue',
            'description' => 'nullable|string',
        ]);

        $file = $validated['file'];
        $path = $file->store('imports/processing', 'local');

        // Create import job
        $jobId = Str::uuid();

        $importJob = [
            'id' => $jobId,
            'tenant_id' => $tenant->id,
            'type' => $validated['type'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mapping' => $validated['mapping'] ?? [],
            'skip_duplicates' => $validated['skip_duplicates'] ?? false,
            'on_error' => $validated['on_error'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'progress' => 0,
            'requested_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Store job data in database or cache
        \Cache::put("import_job:{$jobId}", $importJob, now()->addHours(24));

        // Dispatch job to process import
        dispatch(function () use ($jobId) {
            $importService = app(ImportService::class);
            $importService->processImportJob($jobId);
        });

        return response()->json([
            'success' => true,
            'message' => 'Import job created successfully',
            'data' => [
                'job_id' => $jobId,
                'status' => 'pending',
                'type' => $validated['type'],
                'file_name' => $file->getClientOriginalName(),
            ],
        ], 201);
    }

    /**
     * Get import job status.
     */
    public function show(string $jobId): JsonResponse
    {
        $job = \Cache::get("import_job:{$jobId}");

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => 'Import job not found or expired',
            ], 404);
        }

        // Verify tenant access
        $tenant = tenancy()->tenant;
        if ($job['tenant_id'] !== $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $job['id'],
                'status' => $job['status'],
                'progress' => $job['progress'],
                'processed_rows' => $job['processed_rows'] ?? 0,
                'total_rows' => $job['total_rows'] ?? 0,
                'successful_rows' => $job['successful_rows'] ?? 0,
                'failed_rows' => $job['failed_rows'] ?? 0,
                'error_message' => $job['error_message'] ?? null,
                'completed_at' => $job['completed_at'] ?? null,
            ],
        ]);
    }

    /**
     * Cancel an import job.
     */
    public function cancel(string $jobId): JsonResponse
    {
        $job = \Cache::get("import_job:{$jobId}");

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => 'Import job not found or expired',
            ], 404);
        }

        $tenant = tenancy()->tenant;
        if ($job['tenant_id'] !== $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        if (! in_array($job['status'], ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel import that has completed',
            ], 400);
        }

        $job['status'] = 'cancelled';
        \Cache::put("import_job:{$jobId}", $job, now()->addHours(24));

        return response()->json([
            'success' => true,
            'message' => 'Import job cancelled successfully',
        ]);
    }

    /**
     * Get import templates.
     */
    public function templates(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'type' => 'required|in:users,invoices,subscriptions,activities,analytics,custom',
        ]);

        $importService = app(ImportService::class);
        $template = $importService->getImportTemplate($validated['type']);

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $validated['type'],
                'template' => $template,
            ],
        ]);
    }

    /**
     * Download import template.
     */
    public function downloadTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:users,invoices,subscriptions,activities,analytics,custom',
            'format' => 'required|in:csv,excel',
        ]);

        $importService = app(ImportService::class);
        $fileInfo = $importService->generateImportTemplate(
            $validated['type'],
            $validated['format']
        );

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $fileInfo['url'],
                'file_name' => $fileInfo['name'],
            ],
        ]);
    }

    /**
     * Get import history.
     */
    public function history(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        // This would typically query a imports table
        // For now, we'll return cache data
        $cachePrefix = 'import_job:';
        $keys = \Cache::get("{$cachePrefix}*");
        $jobs = [];

        foreach ($keys as $key) {
            $job = \Cache::get($key);
            if ($job && $job['tenant_id'] === $tenant->id) {
                $jobs[] = [
                    'job_id' => $job['id'],
                    'type' => $job['type'],
                    'status' => $job['status'],
                    'file_name' => $job['file_name'],
                    'created_at' => $job['created_at'],
                    'completed_at' => $job['completed_at'] ?? null,
                    'progress' => $job['progress'],
                ];
            }
        }

        usort($jobs, fn ($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }
}
