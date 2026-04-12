<?php

declare(strict_types=1);

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\ReportTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Report Template Controller (Tenant)
 *
 * Tenant-level report template management.
 */
class ReportTemplateController extends Controller
{
    /**
     * Get all report templates.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = ReportTemplate::where(function ($q) use ($tenant) {
            $q->where('tenant_id', $tenant->id)
                ->orWhereNull('tenant_id');
        });

        if ($request->has('is_system')) {
            $isSystem = $request->boolean('is_system');
            $query->whereNull('tenant_id', $isSystem);
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        $templates = $query->orderBy('name')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $templates->items(),
            'pagination' => [
                'total' => $templates->total(),
                'per_page' => $templates->perPage(),
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific report template.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $template = ReportTemplate::where(function ($q) use ($tenant) {
            $q->where('tenant_id', $tenant->id)
                ->orWhereNull('tenant_id');
        })->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    /**
     * Create a new report template.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'template' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $template = ReportTemplate::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? 'custom',
            'template' => $validated['template'],
            'is_system' => false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report template created successfully',
            'data' => $template,
        ], 201);
    }

    /**
     * Update a report template.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $template = ReportTemplate::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($template->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update system template',
            ], 400);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'template' => 'sometimes|array',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Report template updated successfully',
            'data' => $template,
        ]);
    }

    /**
     * Delete a report template.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $template = ReportTemplate::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($template->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system template',
            ], 400);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report template deleted successfully',
        ]);
    }

    /**
     * Create report from template.
     */
    public function createFromTemplate(string $templateId, Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $template = ReportTemplate::findOrFail($templateId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parameters' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $report = ReportTemplate::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'description' => $template->description,
            'category' => $template->category,
            'template' => array_merge($template->template, [
                'parameters' => $validated['parameters'] ?? [],
            ]),
            'is_system' => false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report created from template successfully',
            'data' => $report,
        ], 201);
    }
}
