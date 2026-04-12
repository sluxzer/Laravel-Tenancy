<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessExportJob;
use App\Models\Activity;
use App\Models\AnalyticsEvent;
use App\Models\Export;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\UsageMetric;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Export Service
 *
 * Handles data export operations for various entity types.
 */
class ExportService
{
    protected array $supportedFormats = ['csv', 'json', 'xlsx', 'xml'];

    protected array $supportedEntities = [
        'users',
        'subscriptions',
        'invoices',
        'payments',
        'transactions',
        'activities',
        'notifications',
        'analytics_events',
        'usage_metrics',
    ];

    /**
     * Create a new export job.
     */
    public function createExport(User $user, array $data): Export
    {
        $tenant = tenancy()->tenant;

        $export = Export::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'entity_type' => $data['entity_type'],
            'format' => $data['format'],
            'filters' => $data['filters'] ?? [],
            'fields' => $data['fields'] ?? [],
            'status' => 'pending',
            'file_path' => null,
            'file_size' => null,
            'row_count' => null,
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
            'expires_at' => now()->addDays(7),
        ]);

        // Dispatch the export job
        ProcessExportJob::dispatch($export);

        return $export;
    }

    /**
     * Process export (called from job).
     */
    public function processExport(Export $export): void
    {
        $export->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $data = $this->fetchExportData($export);
            $filePath = $this->generateExportFile($export, $data);
            $fileSize = Storage::disk('exports')->size($filePath);

            $export->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'row_count' => count($data),
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Export processing failed', [
                'export_id' => $export->id,
                'error' => $e->getMessage(),
            ]);

            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Fetch data for export based on entity type.
     */
    protected function fetchExportData(Export $export): array
    {
        return match ($export->entity_type) {
            'users' => $this->fetchUsersData($export),
            'subscriptions' => $this->fetchSubscriptionsData($export),
            'invoices' => $this->fetchInvoicesData($export),
            'payments' => $this->fetchPaymentsData($export),
            'transactions' => $this->fetchTransactionsData($export),
            'activities' => $this->fetchActivitiesData($export),
            'notifications' => $this->fetchNotificationsData($export),
            'analytics_events' => $this->fetchAnalyticsData($export),
            'usage_metrics' => $this->fetchUsageMetricsData($export),
            default => throw new \Exception("Unsupported entity type: {$export->entity_type}"),
        };
    }

    /**
     * Fetch users data.
     */
    protected function fetchUsersData(Export $export): array
    {
        $query = User::where('tenant_id', $export->tenant_id);

        foreach ($export->filters as $filter) {
            $query->where($filter['field'], $filter['operator'] ?? '=', $filter['value']);
        }

        $fields = ! empty($export->fields) ? $export->fields : ['*'];

        return $query->select($fields)->get()->toArray();
    }

    /**
     * Fetch subscriptions data.
     */
    protected function fetchSubscriptionsData(Export $export): array
    {
        $query = Subscription::where('tenant_id', $export->tenant_id)
            ->with('plan');

        foreach ($export->filters as $filter) {
            $query->where($filter['field'], $filter['operator'] ?? '=', $filter['value']);
        }

        $fields = ! empty($export->fields) ? $export->fields : ['*'];

        return $query->select($fields)->get()->toArray();
    }

    /**
     * Fetch invoices data.
     */
    protected function fetchInvoicesData(Export $export): array
    {
        $query = Invoice::where('tenant_id', $export->tenant_id)
            ->with(['subscription', 'user']);

        foreach ($export->filters as $filter) {
            $query->where($filter['field'], $filter['operator'] ?? '=', $filter['value']);
        }

        $fields = ! empty($export->fields) ? $export->fields : ['*'];

        return $query->select($fields)->get()->toArray();
    }

    /**
     * Fetch payments data.
     */
    protected function fetchPaymentsData(Export $export): array
    {
        $query = Payment::where('tenant_id', $export->tenant_id)
            ->with(['invoice', 'subscription', 'user']);

        foreach ($export->filters as $filter) {
            $query->where($filter['field'], $filter['operator'] ?? '=', $filter['value']);
        }

        $fields = ! empty($export->fields) ? $export->fields : ['*'];

        return $query->select($fields)->get()->toArray();
    }

    /**
     * Fetch transactions data.
     */
    protected function fetchTransactionsData(Export $export): array
    {
        $query = Transaction::where('tenant_id', $export->tenant_id);

        foreach ($export->filters as $filter) {
            $query->where($filter['field'], $filter['operator'] ?? '=', $filter['value']);
        }

        $fields = ! empty($export->fields) ? $export->fields : ['*'];

        return $query->select($fields)->get()->toArray();
    }

    /**
     * Fetch activities data.
     */
    protected function fetchActivitiesData(Export $export): array
    {
        $query = Activity::where('tenant_id', $export->tenant_id)
            ->with(['causer']);

        foreach ($export->filters as $filter) {
            $query->where($filter['field'], $filter['operator'] ?? '=', $filter['value']);
        }

        $fields = ! empty($export->fields) ? $export->fields : ['*'];

        return $query->select($fields)->get()->toArray();
    }

    /**
     * Fetch notifications data.
     */
    protected function fetchNotificationsData(Export $export): array
    {
        $query = Notification::where('tenant_id', $export->tenant_id)
            ->with('notifiable');

        foreach ($export->filters as $filter) {
            $query->where($filter['field'], $filter['operator'] ?? '=', $filter['value']);
        }

        $fields = ! empty($export->fields) ? $export->fields : ['*'];

        return $query->select($fields)->get()->toArray();
    }

    /**
     * Fetch analytics events data.
     */
    protected function fetchAnalyticsData(Export $export): array
    {
        $query = AnalyticsEvent::where('tenant_id', $export->tenant_id);

        foreach ($export->filters as $filter) {
            $query->where($filter['field'], $filter['operator'] ?? '=', $filter['value']);
        }

        $fields = ! empty($export->fields) ? $export->fields : ['*'];

        return $query->select($fields)->get()->toArray();
    }

    /**
     * Fetch usage metrics data.
     */
    protected function fetchUsageMetricsData(Export $export): array
    {
        $query = UsageMetric::where('tenant_id', $export->tenant_id);

        foreach ($export->filters as $filter) {
            $query->where($filter['field'], $filter['operator'] ?? '=', $filter['value']);
        }

        $fields = ! empty($export->fields) ? $export->fields : ['*'];

        return $query->select($fields)->get()->toArray();
    }

    /**
     * Generate export file based on format.
     */
    protected function generateExportFile(Export $export, array $data): string
    {
        $filename = "{$export->entity_type}_{$export->id}_".Str::random(8).".{$export->format}";
        $path = "exports/{$export->tenant_id}/{$filename}";

        $content = match ($export->format) {
            'csv' => $this->toCsv($data),
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            'xml' => $this->toXml($data),
            'xlsx' => $this->toExcel($data),
            default => json_encode($data),
        };

        Storage::disk('exports')->put($path, $content);

        return $path;
    }

    /**
     * Convert data to CSV format.
     */
    protected function toCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $headers = array_keys($data[0]);
        $output = fopen('php://temp', 'r+');

        fputcsv($output, $headers);

        foreach ($data as $row) {
            $values = array_map(fn ($value) => $this->formatCsvValue($value), $row);
            fputcsv($output, $values);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Format value for CSV output.
     */
    protected function formatCsvValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * Convert data to XML format.
     */
    protected function toXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');

        foreach ($data as $row) {
            $item = $xml->addChild('item');
            foreach ($row as $key => $value) {
                $item->addChild(preg_replace('/[^a-zA-Z0-9_]/', '_', $key), htmlspecialchars((string) $value));
            }
        }

        return $xml->asXML();
    }

    /**
     * Convert data to Excel format (simplified).
     */
    protected function toExcel(array $data): string
    {
        // In a real implementation, use PhpSpreadsheet
        return $this->toCsv($data);
    }

    /**
     * Download export file.
     */
    public function downloadExport(Export $export): BinaryFileResponse
    {
        if (! $export->file_path || $export->status !== 'completed') {
            throw new \Exception('Export file not available');
        }

        if ($export->expires_at && $export->expires_at->isPast()) {
            throw new \Exception('Export file has expired');
        }

        return Storage::disk('exports')->download($export->file_path);
    }

    /**
     * Cancel export.
     */
    public function cancelExport(Export $export): bool
    {
        if (! in_array($export->status, ['pending', 'processing'])) {
            return false;
        }

        $export->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        // Delete file if exists
        if ($export->file_path && Storage::disk('exports')->exists($export->file_path)) {
            Storage::disk('exports')->delete($export->file_path);
        }

        return true;
    }

    /**
     * Delete export.
     */
    public function deleteExport(Export $export): bool
    {
        // Delete file if exists
        if ($export->file_path && Storage::disk('exports')->exists($export->file_path)) {
            Storage::disk('exports')->delete($export->file_path);
        }

        return $export->delete();
    }

    /**
     * Get export statistics.
     */
    public function getStats(int $tenantId): array
    {
        return [
            'total_exports' => Export::where('tenant_id', $tenantId)->count(),
            'completed_exports' => Export::where('tenant_id', $tenantId)->where('status', 'completed')->count(),
            'failed_exports' => Export::where('tenant_id', $tenantId)->where('status', 'failed')->count(),
            'pending_exports' => Export::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'processing_exports' => Export::where('tenant_id', $tenantId)->where('status', 'processing')->count(),
            'total_rows_exported' => Export::where('tenant_id', $tenantId)->where('status', 'completed')->sum('row_count'),
            'total_file_size' => Export::where('tenant_id', $tenantId)->where('status', 'completed')->sum('file_size'),
        ];
    }

    /**
     * Get supported export formats.
     */
    public function getSupportedFormats(): array
    {
        return $this->supportedFormats;
    }

    /**
     * Get supported export entities.
     */
    public function getSupportedEntities(): array
    {
        return $this->supportedEntities;
    }

    /**
     * Clean up expired exports.
     */
    public function cleanupExpiredExports(int $tenantId): int
    {
        $expiredExports = Export::where('tenant_id', $tenantId)
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredExports as $export) {
            $this->deleteExport($export);
            $count++;
        }

        return $count;
    }
}
