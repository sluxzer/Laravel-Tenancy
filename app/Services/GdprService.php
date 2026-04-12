<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\DeleteUserDataJob;
use App\Models\GdprRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * GDPR Service
 *
 * Handles GDPR compliance operations including data export, deletion, and consent management.
 */
class GdprService
{
    /**
     * Create a data export request.
     */
    public function createExportRequest(User $user): GdprRequest
    {
        return GdprRequest::create([
            'tenant_id' => tenancy()->tenant->id,
            'user_id' => $user->id,
            'type' => 'export',
            'status' => 'pending',
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Process data export request.
     */
    public function processExportRequest(GdprRequest $request): string
    {
        $request->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $data = $this->collectUserData($request->user);
            $filePath = $this->generateExportFile($request, $data);

            $request->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'completed_at' => now(),
            ]);

            return $filePath;
        } catch (\Exception $e) {
            Log::error('GDPR export failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);

            $request->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Collect user data for export.
     */
    protected function collectUserData(User $user): array
    {
        return [
            'user' => $user->makeHidden(['password', 'remember_token', 'two_factor_secret'])->toArray(),
            'profile' => $user->profile?->toArray() ?? [],
            'roles' => $user->roles->pluck('name')->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'subscriptions' => $user->subscriptions->map(fn ($s) => $s->only([
                'id', 'plan_id', 'status', 'billing_cycle', 'current_period_start', 'current_period_end',
            ]))->toArray(),
            'invoices' => $user->invoices->map(fn ($i) => $i->only([
                'id', 'amount', 'currency_code', 'status', 'due_date', 'paid_at',
            ]))->toArray(),
            'payments' => $user->payments->map(fn ($p) => $p->only([
                'id', 'amount', 'currency_code', 'payment_method', 'gateway', 'status', 'created_at',
            ]))->toArray(),
            'transactions' => $user->transactions->map(fn ($t) => $t->only([
                'id', 'amount', 'currency_code', 'type', 'status', 'created_at',
            ]))->toArray(),
            'activities' => $user->activities()->limit(100)->get()->map(fn ($a) => $a->only([
                'id', 'description', 'subject_type', 'event', 'created_at',
            ]))->toArray(),
            'notifications' => $user->notifications->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
            ])->toArray(),
            'analytics_events' => $user->analyticsEvents()->limit(100)->get()->toArray(),
            'usage_metrics' => $user->usageMetrics()->limit(100)->get()->toArray(),
            'api_tokens' => $user->tokens->map(fn ($t) => $t->only([
                'id', 'name', 'abilities', 'last_used_at', 'created_at',
            ]))->toArray(),
            'audit_logs' => DB::table('audit_logs')
                ->where('user_id', $user->id)
                ->limit(100)
                ->get()
                ->toArray(),
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate export file (JSON format).
     */
    protected function generateExportFile(GdprRequest $request, array $data): string
    {
        $filename = "gdpr_export_{$request->user_id}_{$request->id}_".Str::random(8).'.json';
        $path = "gdpr/exports/{$request->tenant_id}/{$filename}";

        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        Storage::disk('gdpr')->put($path, $content);

        return $path;
    }

    /**
     * Download export file.
     */
    public function downloadExport(GdprRequest $request): BinaryFileResponse
    {
        if (! $request->file_path || $request->status !== 'completed') {
            throw new \Exception('Export file not available');
        }

        if ($request->expires_at && $request->expires_at->isPast()) {
            throw new \Exception('Export file has expired');
        }

        return Storage::disk('gdpr')->download($request->file_path);
    }

    /**
     * Create a deletion request.
     */
    public function createDeletionRequest(User $user, ?string $reason = null): GdprRequest
    {
        return GdprRequest::create([
            'tenant_id' => tenancy()->tenant->id,
            'user_id' => $user->id,
            'type' => 'deletion',
            'status' => 'pending',
            'reason' => $reason,
            'confirmation_token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Confirm deletion request.
     */
    public function confirmDeletion(GdprRequest $request): bool
    {
        if ($request->status !== 'pending') {
            return false;
        }

        if ($request->expires_at->isPast()) {
            throw new \Exception('Deletion request has expired');
        }

        $request->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        // Queue the deletion job
        DeleteUserDataJob::dispatch($request);

        return true;
    }

    /**
     * Process data deletion.
     */
    public function processDeletion(GdprRequest $request): void
    {
        $request->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $this->deleteUserData($request->user);

            $request->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('GDPR deletion failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);

            $request->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete user data.
     */
    protected function deleteUserData(User $user): void
    {
        DB::beginTransaction();

        try {
            // Delete personal data but keep minimal records for business purposes
            $anonymizedEmail = "deleted_{$user->id}@anonymous.local";

            // Update user with anonymized data
            $user->update([
                'email' => $anonymizedEmail,
                'name' => 'Deleted User',
                'password' => bcrypt(Str::random(64)),
                'phone' => null,
                'two_factor_enabled' => false,
                'two_factor_secret' => null,
            ]);

            // Delete profile
            if ($user->profile) {
                $user->profile->update([
                    'first_name' => null,
                    'last_name' => null,
                    'avatar' => null,
                    'bio' => null,
                    'address' => null,
                    'city' => null,
                    'country' => null,
                    'postal_code' => null,
                ]);
            }

            // Delete notifications
            $user->notifications()->delete();

            // Delete API tokens
            $user->tokens()->delete();

            // Delete audit logs (or anonymize)
            DB::table('audit_logs')
                ->where('user_id', $user->id)
                ->update([
                    'user_id' => null,
                    'causer_id' => null,
                ]);

            // Anonymize analytics events
            $user->analyticsEvents()->update([
                'user_id' => null,
                'session_id' => null,
            ]);

            // Anonymize usage metrics
            $user->usageMetrics()->update([
                'user_id' => null,
            ]);

            // Soft delete subscriptions, invoices, payments
            $user->subscriptions()->update([
                'user_id' => null,
            ]);

            $user->invoices()->update([
                'user_id' => null,
            ]);

            $user->payments()->update([
                'user_id' => null,
            ]);

            $user->transactions()->update([
                'user_id' => null,
            ]);

            // Remove user roles
            $user->syncRoles([]);

            // Remove user permissions
            $user->syncPermissions([]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Anonymize user data (alternative to deletion).
     */
    public function anonymizeUser(User $user): void
    {
        DB::beginTransaction();

        try {
            $anonymizedData = [
                'email' => "anonymous_{$user->id}@anonymized.local",
                'name' => 'Anonymous',
                'password' => bcrypt(Str::random(64)),
                'phone' => null,
            ];

            $user->update($anonymizedData);

            // Anonymize profile
            if ($user->profile) {
                $user->profile->update([
                    'first_name' => 'Anonymous',
                    'last_name' => 'User',
                    'avatar' => null,
                    'bio' => null,
                    'address' => null,
                    'city' => null,
                    'country' => null,
                    'postal_code' => null,
                ]);
            }

            // Anonymize related records
            $user->activities()->update([
                'description' => 'Anonymized activity',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel deletion request.
     */
    public function cancelDeletion(GdprRequest $request): bool
    {
        if (! in_array($request->status, ['pending', 'confirmed'])) {
            return false;
        }

        $request->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Get consent status for user.
     */
    public function getConsentStatus(User $user): array
    {
        return [
            'marketing_consent' => $user->marketing_consent ?? false,
            'analytics_consent' => $user->analytics_consent ?? true,
            'cookies_consent' => $user->cookies_consent ?? true,
            'consent_updated_at' => $user->consent_updated_at,
            'consent_version' => $user->consent_version ?? '1.0',
        ];
    }

    /**
     * Update user consent.
     */
    public function updateConsent(User $user, array $consent): void
    {
        $user->update([
            'marketing_consent' => $consent['marketing_consent'] ?? $user->marketing_consent,
            'analytics_consent' => $consent['analytics_consent'] ?? $user->analytics_consent,
            'cookies_consent' => $consent['cookies_consent'] ?? $user->cookies_consent,
            'consent_updated_at' => now(),
            'consent_version' => $consent['consent_version'] ?? '1.0',
        ]);

        // Log consent change
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'marketing_consent' => $user->marketing_consent,
                'analytics_consent' => $user->analytics_consent,
                'cookies_consent' => $user->cookies_consent,
            ])
            ->log('consent_updated');
    }

    /**
     * Get user privacy policy.
     */
    public function getPrivacyPolicy(string $language = 'en'): array
    {
        return [
            'version' => '1.0',
            'last_updated' => now()->toIso8601String(),
            'language' => $language,
            'content' => config('gdpr.privacy_policy') ?? 'Privacy policy content',
        ];
    }

    /**
     * Get deletion status.
     */
    public function getDeletionStatus(User $user): array
    {
        $activeRequest = GdprRequest::where('user_id', $user->id)
            ->where('type', 'deletion')
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->latest()
            ->first();

        return [
            'has_active_request' => $activeRequest !== null,
            'request_status' => $activeRequest?->status,
            'request_created_at' => $activeRequest?->created_at,
            'request_expires_at' => $activeRequest?->expires_at,
        ];
    }

    /**
     * Get GDPR request history.
     */
    public function getRequestHistory(int $tenantId, array $filters = []): array
    {
        $query = GdprRequest::where('tenant_id', $tenantId)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 20)->toArray();
    }

    /**
     * Cleanup expired export files.
     */
    public function cleanupExpiredExports(int $tenantId): int
    {
        $expiredRequests = GdprRequest::where('tenant_id', $tenantId)
            ->where('type', 'export')
            ->where('expires_at', '<', now())
            ->whereNotNull('file_path')
            ->get();

        $count = 0;

        foreach ($expiredRequests as $request) {
            if (Storage::disk('gdpr')->exists($request->file_path)) {
                Storage::disk('gdpr')->delete($request->file_path);
            }
            $request->update(['file_path' => null]);
            $count++;
        }

        return $count;
    }
}
