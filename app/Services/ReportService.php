<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReportRun;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Report Service
 *
 * Handles report generation, templates, and scheduled reports.
 */
class ReportService
{
    /**
     * Generate a custom report.
     */
    public function generateReport(array $config): array
    {
        $tenant = tenancy()->tenant;
        $query = $this->buildReportQuery($config);

        $results = $query->get();

        return [
            'columns' => $config['columns'] ?? [],
            'data' => $results->toArray(),
            'total' => $results->count(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Build report query based on configuration.
     */
    protected function buildReportQuery(array $config): object
    {
        $query = DB::table($config['table']);

        // Apply filters
        if (! empty($config['filters'])) {
            foreach ($config['filters'] as $filter) {
                $query->where($filter['field'], $filter['operator'] ?? '=', $filter['value']);
            }
        }

        // Apply sorting
        if (! empty($config['sort'])) {
            $query->orderBy($config['sort']['field'], $config['sort']['direction'] ?? 'asc');
        }

        // Apply grouping
        if (! empty($config['group_by'])) {
            $query->groupBy($config['group_by']);
        }

        // Apply aggregations
        if (! empty($config['aggregations'])) {
            foreach ($config['aggregations'] as $agg) {
                $column = $agg['column'];
                $alias = $agg['alias'] ?? $column;

                match ($agg['function']) {
                    'count' => $query->selectRaw("COUNT({$column}) as {$alias}"),
                    'sum' => $query->selectRaw("SUM({$column}) as {$alias}"),
                    'avg' => $query->selectRaw("AVG({$column}) as {$alias}"),
                    'min' => $query->selectRaw("MIN({$column}) as {$alias}"),
                    'max' => $query->selectRaw("MAX({$column}) as {$alias}"),
                    default => $query->addSelect($column),
                };
            }
        }

        // Select specific columns
        if (! empty($config['columns'])) {
            $query->select($config['columns']);
        }

        // Limit results
        if (! empty($config['limit'])) {
            $query->limit($config['limit']);
        }

        // Offset for pagination
        if (! empty($config['offset'])) {
            $query->offset($config['offset']);
        }

        return $query;
    }

    /**
     * Create a report template.
     */
    public function createTemplate(User $user, array $data): ReportTemplate
    {
        return ReportTemplate::create([
            'tenant_id' => tenancy()->tenant->id,
            'user_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'config' => $data['config'],
            'is_public' => $data['is_public'] ?? false,
            'tags' => $data['tags'] ?? [],
        ]);
    }

    /**
     * Update a report template.
     */
    public function updateTemplate(ReportTemplate $template, array $data): ReportTemplate
    {
        $template->update([
            'name' => $data['name'] ?? $template->name,
            'description' => $data['description'] ?? $template->description,
            'config' => $data['config'] ?? $template->config,
            'is_public' => $data['is_public'] ?? $template->is_public,
            'tags' => $data['tags'] ?? $template->tags,
        ]);

        return $template;
    }

    /**
     * Create a report from template.
     */
    public function createFromTemplate(ReportTemplate $template, array $overrides = []): array
    {
        $config = array_merge($template->config, $overrides);

        return $this->generateReport($config);
    }

    /**
     * Run a custom report.
     */
    public function runReport(ReportTemplate $template, array $parameters = []): ReportRun
    {
        $config = array_merge($template->config, $parameters);

        $run = ReportRun::create([
            'tenant_id' => tenancy()->tenant->id,
            'user_id' => auth()->id(),
            'template_id' => $template->id,
            'name' => $template->name,
            'config' => $config,
            'status' => 'running',
            'parameters' => $parameters,
        ]);

        try {
            $results = $this->generateReport($config);

            $run->update([
                'status' => 'completed',
                'results' => $results,
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Report run failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);

            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return $run;
    }

    /**
     * Schedule a report run.
     */
    public function scheduleReport(ReportTemplate $template, array $schedule): object
    {
        return DB::table('scheduled_reports')->insertGetId([
            'tenant_id' => tenancy()->tenant->id,
            'user_id' => auth()->id(),
            'template_id' => $template->id,
            'name' => $schedule['name'] ?? $template->name,
            'schedule' => $schedule,
            'status' => 'active',
            'next_run_at' => $this->calculateNextRun($schedule),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Calculate next run time for scheduled report.
     */
    protected function calculateNextRun(array $schedule): string
    {
        $frequency = $schedule['frequency'] ?? 'daily';
        $timezone = $schedule['timezone'] ?? config('app.timezone');

        return match ($frequency) {
            'hourly' => now()->addHour()->timezone($timezone)->toDateTimeString(),
            'daily' => now()->addDay()->setTimeFromTimeString($schedule['time'] ?? '00:00')->timezone($timezone)->toDateTimeString(),
            'weekly' => now()->addWeek()->startOfWeek()->addDays($schedule['day_of_week'] ?? 0)->setTimeFromTimeString($schedule['time'] ?? '00:00')->timezone($timezone)->toDateTimeString(),
            'monthly' => now()->addMonth()->startOfMonth()->addDays($schedule['day_of_month'] ?? 0)->setTimeFromTimeString($schedule['time'] ?? '00:00')->timezone($timezone)->toDateTimeString(),
            default => now()->addDay()->toDateTimeString(),
        };
    }

    /**
     * Get report stats.
     */
    public function getStats(int $tenantId): array
    {
        return [
            'total_reports' => ReportRun::where('tenant_id', $tenantId)->count(),
            'successful_reports' => ReportRun::where('tenant_id', $tenantId)->where('status', 'completed')->count(),
            'failed_reports' => ReportRun::where('tenant_id', $tenantId)->where('status', 'failed')->count(),
            'running_reports' => ReportRun::where('tenant_id', $tenantId)->where('status', 'running')->count(),
            'total_templates' => ReportTemplate::where('tenant_id', $tenantId)->count(),
            'public_templates' => ReportTemplate::where('tenant_id', $tenantId)->where('is_public', true)->count(),
            'avg_runtime' => ReportRun::where('tenant_id', $tenantId)
                ->whereNotNull('completed_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at)) as avg_runtime')
                ->value('avg_runtime'),
        ];
    }

    /**
     * Export report results.
     */
    public function exportReport(ReportRun $run, string $format = 'csv'): string
    {
        $results = $run->results['data'] ?? [];

        return match ($format) {
            'csv' => $this->exportToCsv($results),
            'json' => json_encode($results),
            'xlsx' => $this->exportToExcel($results),
            default => json_encode($results),
        };
    }

    /**
     * Export to CSV format.
     */
    protected function exportToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $headers = array_keys($data[0]);
        $rows = array_map(fn ($row) => array_values($row), $data);

        $output = implode(',', $headers)."\n";

        foreach ($rows as $row) {
            $output .= implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', $row))."\n";
        }

        return $output;
    }

    /**
     * Export to Excel format (simplified).
     */
    protected function exportToExcel(array $data): string
    {
        // In a real implementation, use a library like PhpSpreadsheet
        return $this->exportToCsv($data);
    }

    /**
     * Duplicate a report template.
     */
    public function duplicateTemplate(ReportTemplate $template, User $user): ReportTemplate
    {
        return ReportTemplate::create([
            'tenant_id' => tenancy()->tenant->id,
            'user_id' => $user->id,
            'name' => $template->name.' (Copy)',
            'description' => $template->description,
            'config' => $template->config,
            'is_public' => $template->is_public,
            'tags' => $template->tags,
        ]);
    }

    /**
     * Get popular reports.
     */
    public function getPopularReports(int $tenantId, int $limit = 10): array
    {
        return ReportRun::where('tenant_id', $tenantId)
            ->select('template_id', DB::raw('COUNT(*) as run_count'))
            ->where('status', 'completed')
            ->groupBy('template_id')
            ->orderByDesc('run_count')
            ->limit($limit)
            ->get()
            ->map(fn ($run) => [
                'template' => ReportTemplate::find($run->template_id),
                'run_count' => $run->run_count,
            ])
            ->toArray();
    }

    /**
     * Get report execution history.
     */
    public function getHistory(int $tenantId, array $filters = []): array
    {
        $query = ReportRun::where('tenant_id', $tenantId)
            ->with(['template', 'user'])
            ->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['template_id'])) {
            $query->where('template_id', $filters['template_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        return $query->paginate($filters['per_page'] ?? 20)->toArray();
    }
}
