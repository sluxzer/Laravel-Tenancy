<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\ExchangeRateController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Analytics\AnalyticsController;
use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Auth\TenantAuthController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Billing\RefundController;
use App\Http\Controllers\Billing\SubscriptionController;
use App\Http\Controllers\Billing\TaxSettingsController;
use App\Http\Controllers\Billing\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Export\ExportController;
use App\Http\Controllers\Gdpr\GdprController;
use App\Http\Controllers\Import\ImportController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\Management\ActivityController;
use App\Http\Controllers\Management\NotificationController;
use App\Http\Controllers\Management\NotificationPreferenceController;
use App\Http\Controllers\Report\CustomReportController;
use App\Http\Controllers\Report\ReportRunController;
use App\Http\Controllers\Report\ReportTemplateController;
use App\Http\Controllers\Report\ScheduledReportController;
use App\Http\Controllers\Usage\UsageAlertController;
use App\Http\Controllers\Usage\UsageMetricController;
use App\Http\Controllers\Usage\UsagePricingController;
use App\Http\Controllers\Webhook\WebhookController;
use App\Http\Controllers\Webhook\WebhookHandlerController;
use App\Http\Middleware\ApiKeyAuth;
use App\Http\Middleware\CheckMaintenance;
use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Central API routes for the application
| Includes tenant-specific routes and platform admin routes
|
*/

Route::middleware('api')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['throttle:5,1'])->post('/auth/register', [TenantAuthController::class, 'register']);
    Route::middleware(['throttle:5,1'])->post('/auth/login', [AuthenticatedSessionController::class, 'store']);
    Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');
    Route::middleware(['throttle:3,1'])->post('/auth/forgot-password', [PasswordResetLinkController::class, 'store']);
    Route::middleware(['throttle:3,1'])->post('/auth/reset-password', [NewPasswordController::class, 'store']);
    Route::middleware(['throttle:5,1'])->post('/auth/verify-email', [EmailVerificationNotificationController::class, 'store']);
    Route::post('/auth/confirm-password', [ConfirmedPasswordStatusController::class, 'show']);

    // Two-factor authentication
    Route::post('/auth/two-factor/challenge', [TwoFactorAuthenticationController::class, 'store'])->middleware('password.confirm');
    Route::post('/auth/two-factor/confirm', [ConfirmedTwoFactorAuthenticationController::class, 'store'])->middleware('password.confirm');
    Route::get('/auth/two-factor/qrcode', [TwoFactorQrCodeController::class, 'show'])->middleware('password.confirm');
    Route::post('/auth/two-factor/enable', [TwoFactorAuthenticationController::class, 'enable'])->middleware('password.confirm');
    Route::post('/auth/two-factor/disable', [TwoFactorAuthenticationController::class, 'disable'])->middleware('password.confirm');
    Route::get('/auth/two-factor/recovery-codes', [RecoveryCodeController::class, 'index'])->middleware('password.confirm');
    Route::post('/auth/two-factor/recovery-codes', [RecoveryCodeController::class, 'store'])->middleware('password.confirm');
    Route::delete('/auth/two-factor/recovery-codes/{code}', [RecoveryCodeController::class, 'destroy'])->middleware('password.confirm');

    /*
    |--------------------------------------------------------------------------
    | Platform Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::apiResource('plans', PlanController::class);
        Route::apiResource('tenants', TenantController::class);
        Route::apiResource('currencies', CurrencyController::class);
        Route::apiResource('exchange-rates', ExchangeRateController::class);
        Route::apiResource('tax-rates', TaxRateController::class);
        Route::apiResource('feature-flags', FeatureFlagController::class);
        Route::apiResource('vouchers', VoucherController::class);
        Route::apiResource('invitations', InvitationController::class);

        // Voucher endpoints
        Route::post('/vouchers/bulk-generate', [VoucherController::class, 'bulkGenerate']);
        Route::post('/vouchers/{id}/validate', [VoucherController::class, 'validate']);

        // Invitation endpoints
        Route::post('/invitations/{id}/resend', [InvitationController::class, 'resend']);
        Route::post('/invitations/{id}/cancel', [InvitationController::class, 'cancel']);
        Route::get('/invitations/accept/{token}', [InvitationAcceptController::class, 'accept']);

        // Tax endpoints
        Route::get('/tax/supported-countries', [TaxRateController::class, 'supportedCountries']);
        Route::post('/tax/calculate', [TaxRateController::class, 'calculate']);

        // Tenant endpoints
        Route::post('/tenants/{id}/suspend', [TenantController::class, 'suspend']);
        Route::post('/tenants/{id}/activate', [TenantController::class, 'activate']);
        Route::get('/tenants/{id}/stats', [TenantController::class, 'stats']);
        Route::get('/tenants/{id}/users', [TenantController::class, 'users']);
    });

    /*
    |--------------------------------------------------------------------------
    | Tenant Routes (Path-based: /api/{tenant}/...)
    |--------------------------------------------------------------------------
    */
    Route::prefix('{tenant}')->middleware([ResolveTenant::class, 'tenancy.end'])->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Public Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware([CheckSubscriptionStatus::class, CheckMaintenance::class])->group(function () {
            Route::get('/health', function () {
                return response()->json([
                    'status' => 'ok',
                    'tenant_id' => tenancy()->tenant?->id,
                    'version' => config('app.version'),
                    'timestamp' => now()->toIso8601String(),
                ]);
            });

            Route::prefix('auth')->group(function () {
                Route::middleware(['throttle:5,1'])->post('/register', [TenantAuthController::class, 'register']);
                Route::middleware(['throttle:5,1'])->post('/login', [AuthenticatedSessionController::class, 'store']);
                Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');
                Route::middleware(['throttle:3,1'])->post('/forgot-password', [PasswordResetLinkController::class, 'store']);
                Route::middleware(['throttle:3,1'])->post('/reset-password', [NewPasswordController::class, 'store']);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Authenticated Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware('auth:sanctum')->group(function () {
            /*
            |--------------------------------------------------------------------------
            | User Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('user')->group(function () {
                Route::get('/', [TenantAuthController::class, 'user']);
                Route::put('/', [TenantAuthController::class, 'updateProfile']);
                Route::post('/change-password', [TenantAuthController::class, 'changePassword']);
                Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
                Route::get('/tokens', [TenantAuthController::class, 'tokens']);
                Route::post('/tokens', [TenantAuthController::class, 'createToken']);
                Route::delete('/tokens/{id}', [TenantAuthController::class, 'deleteToken']);
                Route::get('/roles-permissions', [TenantAuthController::class, 'rolesAndPermissions']);
                Route::get('/has-permission', [TenantAuthController::class, 'hasPermission']);
                Route::get('/has-role', [TenantAuthController::class, 'hasRole']);
            });

            /*
            |--------------------------------------------------------------------------
            | Billing Routes
            |--------------------------------------------------------------------------
            */
            Route::middleware([CheckSubscriptionStatus::class, CheckMaintenance::class])->prefix('billing')->group(function () {
                Route::apiResource('subscriptions', SubscriptionController::class);
                Route::apiResource('invoices', InvoiceController::class);
                Route::apiResource('payments', PaymentController::class);
                Route::apiResource('transactions', TransactionController::class);
                Route::apiResource('refunds', RefundController::class);

                // Subscription actions
                Route::post('/subscriptions/{subscription}/upgrade', [SubscriptionController::class, 'upgrade']);
                Route::post('/subscriptions/{subscription}/downgrade', [SubscriptionController::class, 'downgrade']);
                Route::post('/subscriptions/{subscription}/pause', [SubscriptionController::class, 'pause']);
                Route::post('/subscriptions/{subscription}/resume', [SubscriptionController::class, 'resume']);
                Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
                Route::post('/subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew']);
                Route::post('/subscriptions/{subscription}/apply-voucher', [SubscriptionController::class, 'applyVoucher']);

                // Invoice actions
                Route::post('/invoices/{id}/send', [InvoiceController::class, 'send']);
                Route::get('/invoices/{id}/download', [InvoiceController::class, 'download']);
                Route::post('/invoices/{id}/items', [InvoiceController::class, 'addItem']);
                Route::delete('/invoices/{id}/items/{itemId}', [InvoiceController::class, 'removeItem']);

                // Payment methods
                Route::get('/payments/methods', [PaymentController::class, 'paymentMethods']);
                Route::post('/payments/methods', [PaymentController::class, 'addPaymentMethod']);
                Route::delete('/payments/methods/{methodId}', [PaymentController::class, 'removePaymentMethod']);
                Route::post('/payments/methods/{methodId}/default', [PaymentController::class, 'setDefaultPaymentMethod']);

                // Refund actions
                Route::post('/refunds/{id}/process', [RefundController::class, 'process']);
                Route::post('/refunds/{id}/cancel', [RefundController::class, 'cancel']);

                // Currency and tax
                Route::get('/currency/exchange-rate', [App\Http\Controllers\Billing\CurrencyController::class, 'exchangeRate']);
                Route::post('/currency/convert', [App\Http\Controllers\Billing\CurrencyController::class, 'convert']);
                Route::get('/tax/settings', [TaxSettingsController::class, 'index']);
                Route::post('/tax/settings', [TaxSettingsController::class, 'updateSettings']);
                Route::post('/tax/calculate', [TaxSettingsController::class, 'calculate']);
                Route::post('/tax/create-from-country', [TaxSettingsController::class, 'createFromCountry']);

                // Transaction summary
                Route::get('/transactions/summary', [TransactionController::class, 'summary']);

                // Refund summary
                Route::get('/refunds/summary', [RefundController::class, 'summary']);
            });

            /*
            |--------------------------------------------------------------------------
            | Usage Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('usage')->group(function () {
                Route::apiResource('metrics', UsageMetricController::class);
                Route::apiResource('pricing', UsagePricingController::class);
                Route::apiResource('alerts', UsageAlertController::class);

                // Usage actions
                Route::get('/metrics/summary', [UsageMetricController::class, 'summary']);
                Route::get('/metrics/type/{type}', [UsageMetricController::class, 'byType']);
                Route::post('/metrics/bulk', [UsageMetricController::class, 'bulkStore']);
                Route::post('/pricing/calculate', [UsagePricingController::class, 'calculate']);
                Route::post('/alerts/check', [UsageAlertController::class, 'check']);
                Route::post('/alerts/{id}/trigger', [UsageAlertController::class, 'trigger']);
                Route::post('/alerts/{id}/reset', [UsageAlertController::class, 'reset']);
            });

            /*
            |--------------------------------------------------------------------------
            | Management Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('management')->group(function () {
                Route::apiResource('notifications', NotificationController::class);
                Route::apiResource('notification-preferences', NotificationPreferenceController::class);
                Route::apiResource('activities', ActivityController::class);
                Route::apiResource('invitations', App\Http\Controllers\Management\InvitationController::class);
                Route::apiResource('feature-flags', App\Http\Controllers\Management\FeatureFlagController::class);

                // Notification actions
                Route::post('/notifications/{id}/send', [NotificationController::class, 'send']);
                Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
                Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
                Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
                Route::post('/notifications/bulk-send', [NotificationController::class, 'bulkSend']);

                // Notification preferences
                Route::put('/notification-preferences/global', [NotificationPreferenceController::class, 'updateGlobal']);
                Route::post('/notification-preferences/bulk-update', [NotificationPreferenceController::class, 'bulkUpdate']);

                // Activity actions
                Route::get('/activities/feed', [ActivityController::class, 'feed']);
                Route::get('/activities/summary', [ActivityController::class, 'summary']);
                Route::get('/activities/recent', [ActivityController::class, 'recent']);
                Route::get('/activities/type/{type}', [ActivityController::class, 'byType']);
                Route::post('/activities/export', [ActivityController::class, 'export']);

                // Invitation actions
                Route::post('/invitations/{id}/resend', [App\Http\Controllers\Management\InvitationController::class, 'resend']);
                Route::post('/invitations/{id}/cancel', [App\Http\Controllers\Management\InvitationController::class, 'cancel']);
                Route::get('/invitations/accept/{token}', [App\Http\Controllers\Management\InvitationController::class, 'accept']);

                // Feature flag actions
                Route::post('/feature-flags/check', [App\Http\Controllers\Management\FeatureFlagController::class, 'check']);
                Route::post('/feature-flags/batch-check', [App\Http\Controllers\Management\FeatureFlagController::class, 'batchCheck']);
                Route::get('/feature-flags/enabled', [App\Http\Controllers\Management\FeatureFlagController::class, 'enabled']);
            });

            /*
            |--------------------------------------------------------------------------
            | Audit Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('audit')->group(function () {
                Route::apiResource('logs', AuditLogController::class);

                // Audit actions
                Route::get('/logs/summary', [AuditLogController::class, 'summary']);
                Route::get('/logs/model', [AuditLogController::class, 'forModel']);
                Route::get('/logs/recent', [AuditLogController::class, 'recent']);
                Route::post('/logs/export', [AuditLogController::class, 'export']);
            });

            /*
            |--------------------------------------------------------------------------
            | Webhook Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('webhooks')->group(function () {
                Route::apiResource('webhooks', WebhookController::class);

                // Webhook actions
                Route::post('/webhooks/{id}/toggle', [WebhookController::class, 'toggle']);
                Route::post('/webhooks/{id}/test', [WebhookController::class, 'test']);
                Route::post('/webhooks/{id}/regenerate-secret', [WebhookController::class, 'regenerateSecret']);
                Route::get('/webhooks/{id}/events', [WebhookController::class, 'events']);
                Route::post('/webhooks/{webhookId}/events/{id}/retry', [WebhookController::class, 'retryEvent']);
            });

            /*
            |--------------------------------------------------------------------------
            | Analytics Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('analytics')->group(function () {
                Route::post('/track', [AnalyticsController::class, 'track']);
                Route::post('/track/batch', [AnalyticsController::class, 'batchTrack']);
                Route::apiResource('events', [AnalyticsController::class]);

                // Analytics actions
                Route::get('/events/summary', [AnalyticsController::class, 'summary']);
                Route::get('/events/type/{type}', [AnalyticsController::class, 'byName']);
                Route::get('/events/names', [AnalyticsController::class, 'eventNames']);
                Route::post('/events/export', [AnalyticsController::class, 'export']);
            });

            /*
            |--------------------------------------------------------------------------
            | Report Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('reports')->group(function () {
                Route::apiResource('custom-reports', CustomReportController::class);
                Route::apiResource('report-templates', ReportTemplateController::class);
                Route::apiResource('report-runs', ReportRunController::class);
                Route::apiResource('scheduled-reports', ScheduledReportController::class);

                // Custom report actions
                Route::post('/custom-reports/{id}/run', [CustomReportController::class, 'run']);
                Route::post('/custom-reports/{id}/schedule', [CustomReportController::class, 'schedule']);
                Route::post('/custom-reports/{id}/duplicate', [CustomReportController::class, 'duplicate']);

                // Report template actions
                Route::post('/report-templates/{id}/create-report', [ReportTemplateController::class, 'createFromTemplate']);

                // Report run actions
                Route::get('/report-runs/{id}/results', [ReportRunController::class, 'results']);
                Route::post('/report-runs/{id}/download', [ReportRunController::class, 'download']);
                Route::post('/report-runs/{id}/cancel', [ReportRunController::class, 'cancel']);
                Route::get('/report-runs/stats', [ReportRunController::class, 'stats']);

                // Scheduled report actions
                Route::post('/scheduled-reports/{id}/run', [ScheduledReportController::class, 'runNow']);
                Route::post('/scheduled-reports/{id}/pause', [ScheduledReportController::class, 'pause']);
                Route::post('/scheduled-reports/{id}/resume', [ScheduledReportController::class, 'resume']);
            });

            /*
            |--------------------------------------------------------------------------
            | Export Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('export')->group(function () {
                Route::apiResource('exports', ExportController::class);

                // Export actions
                Route::post('/exports', [ExportController::class, 'store']);
                Route::get('/exports/{id}/download', [ExportController::class, 'download']);
                Route::post('/exports/{id}/cancel', [ExportController::class, 'cancel']);
                Route::delete('/exports/{id}', [ExportController::class, 'destroy']);
                Route::get('/exports/{id}/status', [ExportController::class, 'status']);
                Route::get('/exports/stats', [ExportController::class, 'stats']);
            });

            /*
            |--------------------------------------------------------------------------
            | Import Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('import')->group(function () {
                Route::post('/validate', [ImportController::class, 'validate']);
                Route::post('/preview', [ImportController::class, 'preview']);
                Route::post('/import', [ImportController::class, 'store']);
                Route::get('/jobs/{id}', [ImportController::class, 'show']);
                Route::post('/jobs/{id}/cancel', [ImportController::class, 'cancel']);
                Route::get('/templates', [ImportController::class, 'templates']);
                Route::get('/templates/download', [ImportController::class, 'downloadTemplate']);
                Route::get('/history', [ImportController::class, 'history']);
            });

            /*
            |--------------------------------------------------------------------------
            | GDPR Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('gdpr')->group(function () {
                Route::get('/export-user-data', [GdprController::class, 'exportUserData']);
                Route::get('/export-user-data/download', [GdprController::class, 'downloadUserData']);
                Route::post('/request-deletion', [GdprController::class, 'requestDeletion']);
                Route::post('/deletion/confirm/{token}', [GdprController::class, 'confirmDeletion']);
                Route::post('/deletion/cancel', [GdprController::class, 'cancelDeletion']);
                Route::get('/deletion/status', [GdprController::class, 'deletionStatus']);
                Route::post('/anonymize', [GdprController::class, 'anonymize']);
                Route::get('/consent-status', [GdprController::class, 'consentStatus']);
                Route::put('/consent', [GdprController::class, 'updateConsent']);
            });

            /*
            |--------------------------------------------------------------------------
            | Dashboard Routes
            |--------------------------------------------------------------------------
            */
            Route::middleware([CheckSubscriptionStatus::class, CheckMaintenance::class])->group(function () {
                Route::get('/', [DashboardController::class, 'index']);
                Route::get('/stats', [DashboardController::class, 'stats']);
                Route::get('/recent-activities', [DashboardController::class, 'recentActivities']);
            });
        });
    });
});

/*
|--------------------------------------------------------------------------
| Webhook Handler Routes (External)
|--------------------------------------------------------------------------
|
| Handles webhooks from external providers (Stripe, Xendit, etc.)
| These routes are NOT tenant-scoped
|
*/
Route::middleware([ApiKeyAuth::class, 'throttle:60,1'])->prefix('webhooks')->group(function () {
    Route::post('/stripe', [WebhookHandlerController::class, 'stripe']);
    Route::post('/xendit', [WebhookHandlerController::class, 'xendit']);
    Route::post('/paypal', [WebhookHandlerController::class, 'handle', 'paypal']);
    Route::post('/{provider}', [WebhookHandlerController::class, 'handle']);
});
