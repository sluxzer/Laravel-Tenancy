<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for data export functionality.
    |
    */

    'enabled' => env('EXPORT_ENABLED', true),

    'max_records' => env('EXPORT_MAX_RECORDS', 100000),

    'formats' => [
        'csv' => [
            'enabled' => env('EXPORT_CSV_ENABLED', true),
            'delimiter' => env('EXPORT_CSV_DELIMITER', ','),
            'enclosure' => env('EXPORT_CSV_ENCLOSURE', '"'),
            'escape' => env('EXPORT_CSV_ESCAPE', '\\'),
        ],
        'json' => [
            'enabled' => env('EXPORT_JSON_ENABLED', true),
            'pretty_print' => env('EXPORT_JSON_PRETTY', true),
        ],
        'xlsx' => [
            'enabled' => env('EXPORT_XLSX_ENABLED', false),
            'max_rows' => env('EXPORT_XLSX_MAX_ROWS', 1048576),
        ],
        'xml' => [
            'enabled' => env('EXPORT_XML_ENABLED', true),
        ],
    ],

    'storage' => [
        'disk' => env('EXPORT_STORAGE_DISK', 'local'),
        'path' => env('EXPORT_STORAGE_PATH', 'exports'),
    ],

    'expiration' => [
        'enabled' => env('EXPORT_EXPIRATION_ENABLED', true),
        'days' => env('EXPORT_EXPIRATION_DAYS', 7),
    ],

    'entities' => [
        'users' => [
            'enabled' => env('EXPORT_USERS_ENABLED', true),
            'fields' => ['id', 'name', 'email', 'role', 'created_at'],
            'filterable' => ['role', 'created_at', 'updated_at'],
        ],
        'subscriptions' => [
            'enabled' => env('EXPORT_SUBSCRIPTIONS_ENABLED', true),
            'fields' => ['id', 'plan_id', 'status', 'billing_cycle', 'current_period_start', 'current_period_end'],
            'filterable' => ['status', 'billing_cycle', 'created_at'],
        ],
        'invoices' => [
            'enabled' => env('EXPORT_INVOICES_ENABLED', true),
            'fields' => ['id', 'user_id', 'amount', 'currency_code', 'status', 'due_date', 'paid_at'],
            'filterable' => ['status', 'due_date', 'paid_at'],
        ],
        'payments' => [
            'enabled' => env('EXPORT_PAYMENTS_ENABLED', true),
            'fields' => ['id', 'invoice_id', 'amount', 'currency_code', 'payment_method', 'gateway', 'status', 'created_at'],
            'filterable' => ['gateway', 'status', 'created_at'],
        ],
        'transactions' => [
            'enabled' => env('EXPORT_TRANSACTIONS_ENABLED', true),
            'fields' => ['id', 'amount', 'currency_code', 'type', 'status', 'created_at'],
            'filterable' => ['type', 'status', 'created_at'],
        ],
        'activities' => [
            'enabled' => env('EXPORT_ACTIVITIES_ENABLED', true),
            'fields' => ['id', 'causer_id', 'description', 'subject_type', 'event', 'created_at'],
            'filterable' => ['event', 'created_at'],
        ],
        'notifications' => [
            'enabled' => env('EXPORT_NOTIFICATIONS_ENABLED', true),
            'fields' => ['id', 'type', 'title', 'message', 'is_read', 'read_at', 'created_at'],
            'filterable' => ['type', 'is_read', 'created_at'],
        ],
        'analytics_events' => [
            'enabled' => env('EXPORT_ANALYTICS_ENABLED', true),
            'fields' => ['id', 'user_id', 'event_name', 'properties', 'created_at'],
            'filterable' => ['event_name', 'created_at'],
        ],
        'usage_metrics' => [
            'enabled' => env('EXPORT_USAGE_METRICS_ENABLED', true),
            'fields' => ['id', 'user_id', 'metric_type', 'metric_value', 'unit', 'period', 'recorded_at'],
            'filterable' => ['metric_type', 'period', 'recorded_at'],
        ],
    ],

    'async' => [
        'enabled' => env('EXPORT_ASYNC_ENABLED', true),
        'queue' => env('EXPORT_QUEUE', 'exports'),
        'timeout' => env('EXPORT_TIMEOUT', 3600),
    ],

];
