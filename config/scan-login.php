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
];
