<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reports Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for report generation and scheduling.
    |
    */

    'enabled' => env('REPORTS_ENABLED', true),

    'max_execution_time' => env('REPORTS_MAX_EXECUTION_TIME', 3600), // 1 hour

    'max_results' => env('REPORTS_MAX_RESULTS', 100000),

    'formats' => [
        'csv' => [
            'enabled' => env('REPORTS_CSV_ENABLED', true),
            'delimiter' => env('REPORTS_CSV_DELIMITER', ','),
            'enclosure' => env('REPORTS_CSV_ENCLOSURE', '"'),
        ],
        'json' => [
            'enabled' => env('REPORTS_JSON_ENABLED', true),
            'pretty_print' => env('REPORTS_JSON_PRETTY', true),
        ],
        'xlsx' => [
            'enabled' => env('REPORTS_XLSX_ENABLED', false),
            'max_rows' => env('REPORTS_XLSX_MAX_ROWS', 1048576),
        ],
    ],

    'storage' => [
        'disk' => env('REPORTS_STORAGE_DISK', 'local'),
        'path' => env('REPORTS_STORAGE_PATH', 'reports'),
    ],

    'expiration' => [
        'enabled' => env('REPORTS_EXPIRATION_ENABLED', true),
        'results_days' => env('REPORTS_RESULTS_EXPIRATION_DAYS', 30),
        'templates_days' => env('REPORTS_TEMPLATES_EXPIRATION_DAYS', 0), // Never expire
    ],

    'scheduling' => [
        'enabled' => env('REPORTS_SCHEDULING_ENABLED', true),
        'timezone' => env('REPORTS_TIMEZONE', config('app.timezone')),
        'max_frequency' => env('REPORTS_MAX_FREQUENCY', 'hourly'), // hourly, daily, weekly, monthly
        'run_timeout' => env('REPORTS_RUN_TIMEOUT', 3600),
    ],

    'async' => [
        'enabled' => env('REPORTS_ASYNC_ENABLED', true),
        'queue' => env('REPORTS_QUEUE', 'reports'),
        'timeout' => env('REPORTS_ASYNC_TIMEOUT', 3600),
    ],

    'notification' => [
        'enabled' => env('REPORTS_NOTIFICATION_ENABLED', true),
        'on_complete' => env('REPORTS_NOTIFY_ON_COMPLETE', true),
        'on_failure' => env('REPORTS_NOTIFY_ON_FAILURE', true),
        'email_recipient' => env('REPORTS_EMAIL_RECIPIENT'),
    ],

    'permissions' => [
        'public_templates' => env('REPORTS_PUBLIC_TEMPLATES', false),
        'require_role' => env('REPORTS_REQUIRE_ROLE', 'admin'),
        'shareable' => env('REPORTS_SHAREABLE', true),
    ],

];
