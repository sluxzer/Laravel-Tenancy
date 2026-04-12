<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for payment processing, refunds, and billing.
    |
    */

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'stripe'),

    'gateways' => [
        'stripe' => [
            'enabled' => env('PAYMENT_STRIPE_ENABLED', true),
            'currencies' => ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY'],
            'min_amount' => 0.50,
            'max_amount' => 999999.99,
        ],
        'xendit' => [
            'enabled' => env('PAYMENT_XENDIT_ENABLED', false),
            'currencies' => ['IDR', 'PHP', 'USD', 'SGD', 'MYR', 'VND', 'THB'],
            'min_amount' => 1000,
            'max_amount' => 1000000000,
        ],
        'paypal' => [
            'enabled' => env('PAYMENT_PAYPAL_ENABLED', false),
            'currencies' => ['USD', 'EUR', 'GBP', 'CAD', 'AUD'],
            'min_amount' => 0.30,
            'max_amount' => 100000.00,
        ],
    ],

    'refund' => [
        'enabled' => env('REFUND_ENABLED', true),
        'window_days' => env('REFUND_WINDOW_DAYS', 180),
        'auto_approve' => env('REFUND_AUTO_APPROVE', false),
        'require_confirmation' => env('REFUND_REQUIRE_CONFIRMATION', true),
        'refund_fees' => env('REFUND_REFUND_FEES', false),
    ],

    'subscription' => [
        'grace_period_days' => env('SUBSCRIPTION_GRACE_PERIOD', 3),
        'retry_attempts' => env('SUBSCRIPTION_RETRY_ATTEMPTS', 3),
        'retry_interval_hours' => env('SUBSCRIPTION_RETRY_INTERVAL', 24),
        'prorate_upgrades' => env('SUBSCRIPTION_PRORATE_UPGRADES', true),
        'prorate_downgrades' => env('SUBSCRIPTION_PRORATE_DOWNGRADES', false),
    ],

    'invoice' => [
        'auto_generate' => env('INVOICE_AUTO_GENERATE', true),
        'generate_days_before' => env('INVOICE_GENERATE_DAYS_BEFORE', 7),
        'due_days' => env('INVOICE_DUE_DAYS', 30),
        'reminders' => [
            'enabled' => env('INVOICE_REMINDERS_ENABLED', true),
            'days_before' => explode(',', env('INVOICE_REMINDER_DAYS', '7,3,1')),
        ],
    ],

    'currency' => [
        'default' => env('CURRENCY_DEFAULT', 'USD'),
        'auto_convert' => env('CURRENCY_AUTO_CONVERT', true),
        'exchange_rate_ttl' => env('CURRENCY_EXCHANGE_RATE_TTL', 3600),
    ],

    'webhook' => [
        'timeout_seconds' => env('PAYMENT_WEBHOOK_TIMEOUT', 30),
        'max_retries' => env('PAYMENT_WEBHOOK_MAX_RETRIES', 5),
        'retry_delay_seconds' => env('PAYMENT_WEBHOOK_RETRY_DELAY', 60),
    ],

];
