<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Import Service
 *
 * Handles data import operations for various entity types.
 */
class ImportService
{
    protected array $supportedFormats = ['csv', 'json', 'xlsx', 'xml'];

    protected array $supportedEntities = [
        'users',
        'subscriptions',
        'invoices',
        'payments',
    ];

    /**
     * Create a new import job.
     */
    public function createImport(User $user, array $data): Import
    {
        $tenant = tenancy()->tenant;

        $file = $data['file'];
        $fileName = "import_{$data['entity_type']}_".Str::random(8).'.'.$file->getClientOriginalExtension();
        $filePath = $file->storeAs("imports/{$tenant->id}", $fileName, 'imports');

        $import = Import::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'entity_type' => $data['entity_type'],
            'format' => $this->detectFormat($file),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'status' => 'pending',
            'options' => $data['options'] ?? [],
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
            'total_rows' => null,
            'processed_rows' => null,
            'successful_rows' => null,
            'failed_rows' => null,
            'validation_errors' => [],
        ]);

        // Dispatch the import job
        ProcessImportJob::dispatch($import);

        return $import;
    }

    /**
     * Detect file format.
     */
    protected function detectFormat(UploadedFile $file): string
    {
        return strtolower($file->getClientOriginalExtension());
    }

    /**
     * Validate import file.
     */
    public function validateImport(array $data): array
    {
        $validator = Validator::make($data, [
            'entity_type' => 'required|in:'.implode(',', $this->supportedEntities),
            'file' => 'required|file|mimes:csv,json,xlsx,xml',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return [
            'valid' => true,
            'format' => $this->detectFormat($data['file']),
            'entity_type' => $data['entity_type'],
            'file_size' => $data['file']->getSize(),
        ];
    }

    /**
     * Preview import data.
     */
    public function previewImport(array $data): array
    {
        $file = $data['file'];
        $format = $this->detectFormat($file);
        $content = $file->get();

        $rows = match ($format) {
            'csv' => $this->parseCsv($content),
            'json' => $this->parseJson($content),
            'xml' => $this->parseXml($content),
            'xlsx' => $this->parseExcel($content),
            default => throw new \Exception("Unsupported format: {$format}"),
        };

        $preview = array_slice($rows, 0, 10);

        return [
            'total_rows' => count($rows),
            'preview' => $preview,
            'columns' => ! empty($preview) ? array_keys($preview[0]) : [],
            'format' => $format,
        ];
    }

    /**
     * Parse CSV content.
     */
    protected function parseCsv(string $content): array
    {
        $rows = [];
        $lines = explode("\n", $content);

        if (empty($lines)) {
            return [];
        }

        $headers = str_getcsv($lines[0]);

        for ($i = 1; $i < count($lines); $i++) {
            if (empty(trim($lines[$i]))) {
                continue;
            }

            $values = str_getcsv($lines[$i]);

            if (count($values) === count($headers)) {
                $rows[] = array_combine($headers, $values);
            }
        }

        return $rows;
    }

    /**
     * Parse JSON content.
     */
    protected function parseJson(string $content): array
    {
        $data = json_decode($content, true);

        if (! is_array($data)) {
            return [];
        }

        // If data is associative with a "data" key
        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        // If data is array of arrays
        if (! empty($data) && is_array($data[0] ?? null)) {
            return $data;
        }

        // If data is a single object, wrap it
        return [$data];
    }

    /**
     * Parse XML content.
     */
    protected function parseXml(string $content): array
    {
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            return [];
        }

        $json = json_encode($xml);
        $data = json_decode($json, true);

        // Flatten structure to array of items
        if (isset($data['item']) && isset($data['item'][0])) {
            return $data['item'];
        }

        if (isset($data['item'])) {
            return [$data['item']];
        }

        return [];
    }

    /**
     * Parse Excel content (simplified).
     */
    protected function parseExcel(string $content): array
    {
        // In a real implementation, use PhpSpreadsheet
        // For now, assume CSV
        return $this->parseCsv($content);
    }

    /**
     * Process import (called from job).
     */
    public function processImport(Import $import): void
    {
        $import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $content = Storage::disk('imports')->get($import->file_path);
            $rows = match ($import->format) {
                'csv' => $this->parseCsv($content),
                'json' => $this->parseJson($content),
                'xml' => $this->parseXml($content),
                'xlsx' => $this->parseExcel($content),
                default => throw new \Exception("Unsupported format: {$import->format}"),
            };

            $import->update(['total_rows' => count($rows)]);

            $results = $this->importData($import, $rows);

            $import->update([
                'status' => 'completed',
                'processed_rows' => $results['processed'],
                'successful_rows' => $results['successful'],
                'failed_rows' => $results['failed'],
                'validation_errors' => $results['errors'],
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Import processing failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);

            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Import data based on entity type.
     */
    protected function importData(Import $import, array $rows): array
    {
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                match ($import->entity_type) {
                    'users' => $this->importUser($import->tenant_id, $row),
                    'subscriptions' => $this->importSubscription($import->tenant_id, $row),
                    'invoices' => $this->importInvoice($import->tenant_id, $row),
                    'payments' => $this->importPayment($import->tenant_id, $row),
                };

                $successful++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'row' => $index + 1,
                    'data' => $row,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'processed' => $successful + $failed,
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Import a user.
     */
    protected function importUser(int $tenantId, array $data): User
    {
        $email = $data['email'] ?? throw new \Exception('Email is required');

        $user = User::where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();

        if ($user) {
            // Update existing user
            $user->update(array_merge($data, ['email' => $email]));
        } else {
            // Create new user
            $user = User::create(array_merge($data, [
                'tenant_id' => $tenantId,
                'email' => $email,
                'password' => bcrypt($data['password'] ?? Str::random(16)),
            ]));
        }

        return $user;
    }

    /**
     * Import a subscription.
     */
    protected function importSubscription(int $tenantId, array $data): Subscription
    {
        $planId = $data['plan_id'] ?? throw new \Exception('Plan ID is required');
        $userId = $data['user_id'] ?? throw new \Exception('User ID is required');

        // Verify plan and user exist
        $plan = Plan::findOrFail($planId);
        $user = User::where('tenant_id', $tenantId)->findOrFail($userId);

        $subscription = Subscription::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'plan_id' => $planId,
            'status' => $data['status'] ?? 'active',
            'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
            'current_period_start' => $data['current_period_start'] ?? now(),
            'current_period_end' => $data['current_period_end'] ?? now()->addMonth(),
        ]);

        return $subscription;
    }

    /**
     * Import an invoice.
     */
    protected function importInvoice(int $tenantId, array $data): Invoice
    {
        $userId = $data['user_id'] ?? throw new \Exception('User ID is required');

        // Verify user exists
        $user = User::where('tenant_id', $tenantId)->findOrFail($userId);

        $invoice = Invoice::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'subscription_id' => $data['subscription_id'] ?? null,
            'amount' => $data['amount'],
            'currency_code' => $data['currency_code'] ?? 'USD',
            'status' => $data['status'] ?? 'unpaid',
            'due_date' => $data['due_date'] ?? now()->addDays(30),
            'paid_at' => $data['paid_at'] ?? null,
        ]);

        return $invoice;
    }

    /**
     * Import a payment.
     */
    protected function importPayment(int $tenantId, array $data): Payment
    {
        $userId = $data['user_id'] ?? throw new \Exception('User ID is required');

        // Verify user exists
        $user = User::where('tenant_id', $tenantId)->findOrFail($userId);

        $payment = Payment::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'invoice_id' => $data['invoice_id'] ?? null,
            'subscription_id' => $data['subscription_id'] ?? null,
            'amount' => $data['amount'],
            'currency_code' => $data['currency_code'] ?? 'USD',
            'payment_method' => $data['payment_method'] ?? 'unknown',
            'gateway' => $data['gateway'] ?? 'manual',
            'transaction_id' => $data['transaction_id'] ?? null,
            'status' => $data['status'] ?? 'paid',
        ]);

        return $payment;
    }

    /**
     * Cancel import.
     */
    public function cancelImport(Import $import): bool
    {
        if (! in_array($import->status, ['pending', 'processing'])) {
            return false;
        }

        $import->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Get import history.
     */
    public function getHistory(int $tenantId, array $filters = []): array
    {
        $query = Import::where('tenant_id', $tenantId)
            ->with('user')
            ->orderBy('created_at', 'desc');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        return $query->paginate($filters['per_page'] ?? 20)->toArray();
    }

    /**
     * Get import statistics.
     */
    public function getStats(int $tenantId): array
    {
        return [
            'total_imports' => Import::where('tenant_id', $tenantId)->count(),
            'completed_imports' => Import::where('tenant_id', $tenantId)->where('status', 'completed')->count(),
            'failed_imports' => Import::where('tenant_id', $tenantId)->where('status', 'failed')->count(),
            'pending_imports' => Import::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'total_rows_imported' => Import::where('tenant_id', $tenantId)->where('status', 'completed')->sum('successful_rows'),
            'total_rows_failed' => Import::where('tenant_id', $tenantId)->where('status', 'completed')->sum('failed_rows'),
        ];
    }

    /**
     * Get import templates.
     */
    public function getTemplates(): array
    {
        return [
            'users' => [
                'required_fields' => ['email'],
                'optional_fields' => ['name', 'password', 'phone'],
                'sample_csv' => "email,name,password,phone\nuser@example.com,John Doe,password123,+1234567890\n",
            ],
            'subscriptions' => [
                'required_fields' => ['plan_id', 'user_id'],
                'optional_fields' => ['status', 'billing_cycle', 'current_period_start', 'current_period_end'],
                'sample_csv' => "plan_id,user_id,status,billing_cycle\n1,1,active,monthly\n2,2,active,yearly\n",
            ],
            'invoices' => [
                'required_fields' => ['user_id', 'amount'],
                'optional_fields' => ['subscription_id', 'currency_code', 'status', 'due_date'],
                'sample_csv' => "user_id,amount,currency_code,status,due_date\n1,99.99,USD,unpaid,2026-05-12\n",
            ],
            'payments' => [
                'required_fields' => ['user_id', 'amount'],
                'optional_fields' => ['invoice_id', 'subscription_id', 'currency_code', 'payment_method', 'gateway', 'status'],
                'sample_csv' => "user_id,amount,currency_code,payment_method,gateway,status\n1,99.99,USD,card,stripe,paid\n",
            ],
        ];
    }

    /**
     * Download import template.
     */
    public function downloadTemplate(string $entityType): string
    {
        $templates = $this->getTemplates();

        if (! isset($templates[$entityType])) {
            throw new \Exception("Template not found for entity type: {$entityType}");
        }

        return $templates[$entityType]['sample_csv'];
    }

    /**
     * Get supported import formats.
     */
    public function getSupportedFormats(): array
    {
        return $this->supportedFormats;
    }

    /**
     * Get supported import entities.
     */
    public function getSupportedEntities(): array
    {
        return $this->supportedEntities;
    }
}
