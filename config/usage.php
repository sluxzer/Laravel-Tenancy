<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Usage Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for usage tracking, pricing, and alerts.
    |
    */

    'enabled' => env('USAGE_ENABLED', true),

    'tracking' => [
        'enabled' => env('USAGE_TRACKING_ENABLED', true),
        'period' => env('USAGE_TRACKING_PERIOD', 'monthly'),
        'aggregate_period' => env('USAGE_AGGREGATE_PERIOD', 'daily'),
    ],

    'pricing' => [
        'default_model' => env('USAGE_PRICING_DEFAULT_MODEL', 'per_unit'),
        'models' => ['per_unit', 'tiered', 'volume', 'flat_rate'],
        'currency' => env('USAGE_PRICING_CURRENCY', 'USD'),
    ],

    'metrics' => [
        'api_calls' => [
            'enabled' => env('USAGE_METRIC_API_CALLS_ENABLED', true),
            'unit' => 'count',
            'default_limit' => env('USAGE_API_CALLS_DEFAULT_LIMIT', 10000),
        ],
        'storage' => [
            'enabled' => env('USAGE_METRIC_STORAGE_ENABLED', true),
            'unit' => 'bytes',
            'default_limit' => env('USAGE_STORAGE_DEFAULT_LIMIT', 10737418240), // 10GB
        ],
        'bandwidth' => [
            'enabled' => env('USAGE_METRIC_BANDWIDTH_ENABLED', true),
            'unit' => 'bytes',
            'default_limit' => env('USAGE_BANDWIDTH_DEFAULT_LIMIT', 107374182400), // 100GB
        ],
        'users' => [
            'enabled' => env('USAGE_METRIC_USERS_ENABLED', true),
            'unit' => 'count',
            'default_limit' => env('USAGE_USERS_DEFAULT_LIMIT', 5),
        ],
        'emails' => [
            'enabled' => env('USAGE_METRIC_EMAILS_ENABLED', true),
            'unit' => 'count',
            'default_limit' => env('USAGE_EMAILS_DEFAULT_LIMIT', 1000),
        ],
    ],

    'alerts' => [
        'enabled' => env('USAGE_ALERTS_ENABLED', true),
        'check_interval' => env('USAGE_ALERTS_CHECK_INTERVAL', 3600), // 1 hour
        'cooldown' => env('USAGE_ALERTS_COOLDOWN', 86400), // 24 hours
        'conditions' => [
            'threshold' => env('USAGE_ALERTS_THRESHOLD_ENABLED', true),
            'rate' => env('USAGE_ALERTS_RATE_ENABLED', true),
            'anomaly' => env('USAGE_ALERTS_ANOMALY_ENABLED', false),
        ],
        'notification_channels' => [
            'email' => env('USAGE_ALERT_EMAIL', true),
            'in_app' => env('USAGE_ALERT_IN_APP', true),
            'webhook' => env('USAGE_ALERT_WEBHOOK', false),
        ],
    ],

    'overage' => [
        'enabled' => env('USAGE_OVERAGE_ENABLED', true),
        'action' => env('USAGE_OVERAGE_ACTION', 'warn'), // warn, block, charge
        'charge_rate' => env('USAGE_OVERAGE_CHARGE_RATE', 0.001), // per unit
        'block_access' => env('USAGE_OVERAGE_BLOCK_ACCESS', false),
    ],

    'forecast' => [
        'enabled' => env('USAGE_FORECAST_ENABLED', true),
        'months' => env('USAGE_FORECAST_MONTHS', 3),
        'model' => env('USAGE_FORECAST_MODEL', 'linear'),
    ],

    'retention' => [
        'raw_data_days' => env('USAGE_RAW_DATA_RETENTION', 90),
        'aggregated_data_days' => env('USAGE_AGGREGATED_DATA_RETENTION', 365),
    ],

];
