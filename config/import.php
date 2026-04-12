<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Import Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for data import functionality.
    |
    */

    'enabled' => env('IMPORT_ENABLED', true),

    'max_file_size' => env('IMPORT_MAX_FILE_SIZE', 52428800), // 50MB in bytes

    'max_records' => env('IMPORT_MAX_RECORDS', 10000),

    'formats' => [
        'csv' => [
            'enabled' => env('IMPORT_CSV_ENABLED', true),
            'delimiter' => env('IMPORT_CSV_DELIMITER', ','),
            'enclosure' => env('IMPORT_CSV_ENCLOSURE', '"'),
            'escape' => env('IMPORT_CSV_ESCAPE', '\\'),
            'has_header' => env('IMPORT_CSV_HAS_HEADER', true),
        ],
        'json' => [
            'enabled' => env('IMPORT_JSON_ENABLED', true),
        ],
        'xlsx' => [
            'enabled' => env('IMPORT_XLSX_ENABLED', true),
            'max_rows' => env('IMPORT_XLSX_MAX_ROWS', 10000),
        ],
        'xml' => [
            'enabled' => env('IMPORT_XML_ENABLED', false),
        ],
    ],

    'storage' => [
        'disk' => env('IMPORT_STORAGE_DISK', 'local'),
        'path' => env('IMPORT_STORAGE_PATH', 'imports'),
    ],

    'entities' => [
        'users' => [
            'enabled' => env('IMPORT_USERS_ENABLED', true),
            'required_fields' => ['email'],
            'optional_fields' => ['name', 'password', 'phone'],
            'update_existing' => env('IMPORT_USERS_UPDATE_EXISTING', true),
        ],
        'subscriptions' => [
            'enabled' => env('IMPORT_SUBSCRIPTIONS_ENABLED', true),
            'required_fields' => ['plan_id', 'user_id'],
            'optional_fields' => ['status', 'billing_cycle', 'current_period_start', 'current_period_end'],
            'validate_plan' => env('IMPORT_SUBSCRIPTIONS_VALIDATE_PLAN', true),
        ],
        'invoices' => [
            'enabled' => env('IMPORT_INVOICES_ENABLED', true),
            'required_fields' => ['user_id', 'amount'],
            'optional_fields' => ['subscription_id', 'currency_code', 'status', 'due_date'],
            'validate_user' => env('IMPORT_INVOICES_VALIDATE_USER', true),
        ],
        'payments' => [
            'enabled' => env('IMPORT_PAYMENTS_ENABLED', true),
            'required_fields' => ['user_id', 'amount'],
            'optional_fields' => ['invoice_id', 'subscription_id', 'currency_code', 'payment_method', 'gateway', 'status'],
            'validate_user' => env('IMPORT_PAYMENTS_VALIDATE_USER', true),
        ],
    ],

    'validation' => [
        'enabled' => env('IMPORT_VALIDATION_ENABLED', true),
        'preview_rows' => env('IMPORT_PREVIEW_ROWS', 10),
        'stop_on_error' => env('IMPORT_STOP_ON_ERROR', false),
    ],

    'async' => [
        'enabled' => env('IMPORT_ASYNC_ENABLED', true),
        'queue' => env('IMPORT_QUEUE', 'imports'),
        'timeout' => env('IMPORT_TIMEOUT', 3600),
    ],

    'error_handling' => [
        'continue_on_error' => env('IMPORT_CONTINUE_ON_ERROR', true),
        'max_errors' => env('IMPORT_MAX_ERRORS', 100),
        'email_on_failure' => env('IMPORT_EMAIL_ON_FAILURE', true),
    ],

    'notification' => [
        'enabled' => env('IMPORT_NOTIFICATION_ENABLED', true),
        'notify_on_start' => env('IMPORT_NOTIFY_ON_START', true),
        'notify_on_complete' => env('IMPORT_NOTIFY_ON_COMPLETE', true),
        'notify_on_error' => env('IMPORT_NOTIFY_ON_ERROR', true),
    ],

];
