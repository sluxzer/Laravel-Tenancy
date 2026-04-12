<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GDPR Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for GDPR compliance including data export, deletion, and consent.
    |
    */

    'enabled' => env('GDPR_ENABLED', true),

    'data_retention' => [
        'logs_days' => env('GDPR_LOGS_RETENTION_DAYS', 90),
        'activities_days' => env('GDPR_ACTIVITIES_RETENTION_DAYS', 365),
        'audit_logs_days' => env('GDPR_AUDIT_LOGS_RETENTION_DAYS', 2555), // 7 years
        'notifications_days' => env('GDPR_NOTIFICATIONS_RETENTION_DAYS', 365),
        'exports_days' => env('GDPR_EXPORTS_RETENTION_DAYS', 30),
    ],

    'export' => [
        'enabled' => env('GDPR_EXPORT_ENABLED', true),
        'expires_days' => env('GDPR_EXPORT_EXPIRES_DAYS', 30),
        'format' => env('GDPR_EXPORT_FORMAT', 'json'),
        'email_notification' => env('GDPR_EXPORT_EMAIL_NOTIFICATION', true),
    ],

    'deletion' => [
        'enabled' => env('GDPR_DELETION_ENABLED', true),
        'require_confirmation' => env('GDPR_DELETION_REQUIRE_CONFIRMATION', true),
        'confirmation_token_hours' => env('GDPR_CONFIRMATION_TOKEN_HOURS', 168), // 7 days
        'processing_days' => env('GDPR_DELETION_PROCESSING_DAYS', 30),
        'anonymize_instead' => env('GDPR_ANONYMIZE_INSTEAD', false),
        'notify_user' => env('GDPR_DELETION_NOTIFY_USER', true),
    ],

    'consent' => [
        'enabled' => env('GDPR_CONSENT_ENABLED', true),
        'version' => env('GDPR_CONSENT_VERSION', '1.0'),
        'track_updates' => env('GDPR_CONSENT_TRACK_UPDATES', true),
        'require_marketing_consent' => env('GDPR_REQUIRE_MARKETING_CONSENT', false),
        'require_analytics_consent' => env('GDPR_REQUIRE_ANALYTICS_CONSENT', true),
    ],

    'privacy_policy' => [
        'version' => env('GDPR_PRIVACY_VERSION', '1.0'),
        'last_updated' => env('GDPR_PRIVACY_LAST_UPDATED', '2024-01-01'),
        'url' => env('GDPR_PRIVACY_URL', '/privacy'),
        'require_acceptance' => env('GDPR_REQUIRE_PRIVACY_ACCEPTANCE', true),
    ],

    'cookies' => [
        'enabled' => env('GDPR_COOKIES_ENABLED', true),
        'consent_banner' => env('GDPR_COOKIE_CONSENT_BANNER', true),
        'essential_cookies' => ['session', 'csrf_token', 'remember_me'],
        'functional_cookies' => ['preferences', 'language'],
        'analytics_cookies' => ['google_analytics', 'mixpanel'],
        'marketing_cookies' => ['advertising', 'social_media'],
    ],

    'rights' => [
        'right_to_access' => env('GDPR_RIGHT_TO_ACCESS', true),
        'right_to_rectification' => env('GDPR_RIGHT_TO_RECTIFICATION', true),
        'right_to_erasure' => env('GDPR_RIGHT_TO_ERASURE', true),
        'right_to_restrict' => env('GDPR_RIGHT_TO_RESTRICT', true),
        'right_to_portability' => env('GDPR_RIGHT_TO_PORTABILITY', true),
        'right_to_object' => env('GDPR_RIGHT_TO_OBJECT', true),
    ],

];
