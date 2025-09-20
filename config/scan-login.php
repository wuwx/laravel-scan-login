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
    | QR Code Size
    |
    | The size of the generated QR code in pixels.
    | Valid range: 100-500 pixels. Default: 200 pixels.
    |
    */
    'qr_code_size' => (int) env('SCAN_LOGIN_QR_CODE_SIZE', 200),


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






];
