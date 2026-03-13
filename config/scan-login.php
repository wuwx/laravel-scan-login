<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scan Login Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the Laravel Scan Login
    | package. You can customize these settings to match your application's
    | requirements.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Token Expiry
    |--------------------------------------------------------------------------
    |
    | The number of minutes after which a scan login token will expire.
    | Default is 5 minutes for security reasons.
    |
    */
    'token_expiry_minutes' => env('SCAN_LOGIN_TOKEN_EXPIRY_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | QR Code Size
    |--------------------------------------------------------------------------
    |
    | The size of the QR code in pixels. Default is 200px.
    |
    */
    'qr_code_size' => env('SCAN_LOGIN_QR_CODE_SIZE', 200),

    /*
    |--------------------------------------------------------------------------
    | Login Success Redirect
    |--------------------------------------------------------------------------
    |
    | The URL to redirect users to after successful login via QR code scan.
    | Default is the root path '/'.
    |
    */
    'login_success_redirect' => env('SCAN_LOGIN_SUCCESS_REDIRECT', '/'),

    /*
    |--------------------------------------------------------------------------
    | Polling Interval
    |--------------------------------------------------------------------------
    |
    | The interval in seconds for checking login status updates.
    | Default is 3 seconds for responsive user experience.
    |
    */
    'polling_interval_seconds' => env('SCAN_LOGIN_POLLING_INTERVAL', 3),

    /*
    |--------------------------------------------------------------------------
    | Token Length
    |--------------------------------------------------------------------------
    |
    | The length of the random token string. Default is 64 characters.
    | Higher values provide better security but longer URLs.
    |
    */
    'token_length' => env('SCAN_LOGIN_TOKEN_LENGTH', 64),

    /*
    |--------------------------------------------------------------------------
    | Enable Package
    |--------------------------------------------------------------------------
    |
    | Whether the scan login functionality is enabled. Set to false to
    | disable the entire package functionality.
    |
    */
    'enabled' => env('SCAN_LOGIN_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Enable GeoIP Location
    |--------------------------------------------------------------------------
    |
    | Whether to display geographical location information based on IP address.
    | Requires torann/geoip package to be installed and configured.
    |
    */
    'enable_geoip' => env('SCAN_LOGIN_ENABLE_GEOIP', true),

    /*
    |--------------------------------------------------------------------------
    | Enable Default Event Listeners
    |--------------------------------------------------------------------------
    |
    | Whether to enable the default event listeners (logging).
    | Set to false if you want to register your own listeners.
    |
    */
    'enable_default_listeners' => env('SCAN_LOGIN_ENABLE_DEFAULT_LISTENERS', true),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to prevent abuse and brute force attacks.
    |
    */
    'rate_limit' => [
        // Enable or disable rate limiting
        'enabled' => env('SCAN_LOGIN_RATE_LIMIT_ENABLED', true),

        // Maximum attempts allowed
        'max_attempts' => env('SCAN_LOGIN_RATE_LIMIT_MAX_ATTEMPTS', 10),

        // Time window in minutes
        'decay_minutes' => env('SCAN_LOGIN_RATE_LIMIT_DECAY_MINUTES', 1),

        // Rate limit strategy: 'ip', 'user', 'ip_and_user', 'session'
        'strategy' => env('SCAN_LOGIN_RATE_LIMIT_STRATEGY', 'ip'),

        // Log rate limit hits
        'log_hits' => env('SCAN_LOGIN_RATE_LIMIT_LOG_HITS', true),

        // IP whitelist (array of IP addresses that bypass rate limiting)
        'whitelist' => explode(',', env('SCAN_LOGIN_RATE_LIMIT_WHITELIST', '')),

        // IP blacklist (array of IP addresses that are always blocked)
        'blacklist' => explode(',', env('SCAN_LOGIN_RATE_LIMIT_BLACKLIST', '')),

        // Per-action rate limits (override defaults for specific actions)
        'actions' => [
            'qr_code_generation' => [
                'max_attempts' => env('SCAN_LOGIN_RATE_LIMIT_QR_MAX_ATTEMPTS', 20),
                'decay_minutes' => env('SCAN_LOGIN_RATE_LIMIT_QR_DECAY_MINUTES', 1),
            ],
            'token_claim' => [
                'max_attempts' => env('SCAN_LOGIN_RATE_LIMIT_CLAIM_MAX_ATTEMPTS', 5),
                'decay_minutes' => env('SCAN_LOGIN_RATE_LIMIT_CLAIM_DECAY_MINUTES', 1),
            ],
            'token_consume' => [
                'max_attempts' => env('SCAN_LOGIN_RATE_LIMIT_CONSUME_MAX_ATTEMPTS', 3),
                'decay_minutes' => env('SCAN_LOGIN_RATE_LIMIT_CONSUME_DECAY_MINUTES', 1),
            ],
        ],
    ],
];
