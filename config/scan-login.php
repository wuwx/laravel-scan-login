<?php

// config for Wuwx/LaravelScanLogin
return [
    /*
    |--------------------------------------------------------------------------
    | Scan Login Feature Toggle
    |--------------------------------------------------------------------------
    |
    | This option controls whether the scan login feature is enabled.
    | When disabled, QR codes will not be generated and scan login routes
    | will return 404 responses.
    |
    */
    'enabled' => env('SCAN_LOGIN_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Token Configuration
    |--------------------------------------------------------------------------
    */

    /*
    | Token Expiry Time
    |
    | The number of minutes a login token remains valid before expiring.
    | Valid range: 1-60 minutes. Default: 5 minutes.
    |
    */
    'token_expiry_minutes' => (int) env('SCAN_LOGIN_TOKEN_EXPIRY_MINUTES', 5),

    /*
    | Token Length
    |
    | The length of the generated token string.
    | Valid range: 32-128 characters. Default: 64 characters.
    |
    */
    'token_length' => (int) env('SCAN_LOGIN_TOKEN_LENGTH', 64),

    /*
    |--------------------------------------------------------------------------
    | User Interface Configuration
    |--------------------------------------------------------------------------
    */

    /*
    | Polling Interval
    |
    | The interval in seconds for desktop clients to poll for login status.
    | Valid range: 1-30 seconds. Default: 3 seconds.
    |
    */
    'polling_interval_seconds' => (int) env('SCAN_LOGIN_POLLING_INTERVAL_SECONDS', 3),

    /*
    | Maximum Polling Duration
    |
    | The maximum time in minutes that desktop clients will poll for status.
    | After this time, polling will stop and QR code will be refreshed.
    | Valid range: 1-15 minutes. Default: 10 minutes.
    |
    */
    'max_polling_duration_minutes' => (int) env('SCAN_LOGIN_MAX_POLLING_DURATION_MINUTES', 10),

    /*
    | QR Code Size
    |
    | The size of the generated QR code in pixels.
    | Valid range: 100-500 pixels. Default: 200 pixels.
    |
    */
    'qr_code_size' => (int) env('SCAN_LOGIN_QR_CODE_SIZE', 200),

    /*
    | QR Code Error Correction Level
    |
    | The error correction level for QR codes.
    | Options: 'L' (Low), 'M' (Medium), 'Q' (Quartile), 'H' (High)
    | Default: 'M' (Medium)
    |
    */
    'qr_code_error_correction' => env('SCAN_LOGIN_QR_CODE_ERROR_CORRECTION', 'M'),

    /*
    |--------------------------------------------------------------------------
    | Routing Configuration
    |--------------------------------------------------------------------------
    */

    /*
    | Login Success Redirect
    |
    | The URL to redirect to after successful login.
    | Can be a relative path or absolute URL.
    |
    */
    'login_success_redirect' => env('SCAN_LOGIN_SUCCESS_REDIRECT', '/dashboard'),

    /*
    | Route Prefix
    |
    | The prefix for all scan login routes.
    |
    */
    'route_prefix' => env('SCAN_LOGIN_ROUTE_PREFIX', 'scan-login'),

    /*
    | Route Middleware
    |
    | Middleware to apply to scan login routes.
    |
    */
    'route_middleware' => [
        'web',
        'throttle:60,1', // Rate limiting: 60 requests per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */

    /*
    | Require HTTPS
    |
    | Whether to require HTTPS for scan login functionality.
    | Highly recommended for production environments.
    |
    */
    'require_https' => env('SCAN_LOGIN_REQUIRE_HTTPS', app()->environment('production')),

    /*
    | Rate Limiting
    |
    | Maximum number of QR code generation requests per minute per IP.
    |
    */
    'rate_limit_per_minute' => (int) env('SCAN_LOGIN_RATE_LIMIT_PER_MINUTE', 10),

    /*
    | Maximum Login Attempts
    |
    | Maximum number of login attempts allowed per token.
    | After this limit, the token will be invalidated.
    |
    */
    'max_login_attempts' => (int) env('SCAN_LOGIN_MAX_LOGIN_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Token Cleanup Configuration
    |--------------------------------------------------------------------------
    */

    /*
    | Automatic Token Cleanup
    |
    | Whether to automatically clean up expired tokens using Laravel's model:prune command.
    | When enabled, expired tokens will be automatically deleted based on the schedule.
    |
    */
    'cleanup_expired_tokens' => env('SCAN_LOGIN_CLEANUP_EXPIRED_TOKENS', true),

    /*
    | Cleanup Schedule
    |
    | The cron schedule for automatic token cleanup using model:prune.
    | Default: '0 * * * *' (every hour)
    | Examples:
    |   '0 * * * *' - Every hour
    |   '0 0,6,12,18 * * *' - Every 6 hours
    |   '0 0 * * *' - Daily at midnight
    |
    */
    'cleanup_schedule' => env('SCAN_LOGIN_CLEANUP_SCHEDULE', '0 * * * *'),

    /*
    | Cleanup Batch Size
    |
    | The number of expired tokens to delete in each cleanup batch.
    | This helps prevent database locks on large datasets.
    |
    */
    'cleanup_batch_size' => (int) env('SCAN_LOGIN_CLEANUP_BATCH_SIZE', 1000),



    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | These are used internally to validate configuration values.
    | You should not modify these unless you know what you're doing.
    |
    */
    'validation' => [
        'token_expiry_minutes' => ['integer', 'min:1', 'max:60'],
        'token_length' => ['integer', 'min:32', 'max:128'],
        'polling_interval_seconds' => ['integer', 'min:1', 'max:30'],
        'max_polling_duration_minutes' => ['integer', 'min:1', 'max:15'],
        'qr_code_size' => ['integer', 'min:100', 'max:500'],
        'qr_code_error_correction' => ['string', 'in:L,M,Q,H'],
        'rate_limit_per_minute' => ['integer', 'min:1', 'max:100'],
        'max_login_attempts' => ['integer', 'min:1', 'max:10'],
        'cleanup_schedule' => ['string'],
        'cleanup_batch_size' => ['integer', 'min:100', 'max:10000'],
    ],
];
