<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scan Login Language File - English
    |--------------------------------------------------------------------------
    */

    // Page titles
    'qr_code_page_title' => 'Scan to Login',
    'qr_code_page_subtitle' => 'Scan the QR code below with your mobile device',
    'mobile_confirm_title' => 'Confirm Login',
    'mobile_confirm_subtitle' => 'Please verify the following information before confirming login',

    // Action buttons
    'confirm_login' => 'Confirm Login',
    'cancel_login' => 'Cancel',

    // Status descriptions
    'status' => [
        'pending' => 'Waiting for scan',
        'claimed' => 'Scanned, waiting for confirmation',
        'consumed' => 'Login successful',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
    ],

    // Result messages
    'results' => [
        'login_approved' => [
            'title' => 'Login Successful',
            'message' => 'You have confirmed the login<br>Please return to your computer to continue',
            'hint' => 'This page can be safely closed',
        ],
        'login_cancelled' => [
            'title' => 'Login Cancelled',
            'message' => 'This login request has been cancelled<br>Please scan a new QR code to login',
            'hint' => 'This page can be safely closed',
        ],
        'token_consumed' => [
            'title' => 'QR Code Already Used',
            'message' => 'This QR code has already been used<br>Cannot be scanned again',
            'hint' => 'Please refresh the page on your computer to generate a new QR code',
        ],
        'token_cancelled' => [
            'title' => 'QR Code Cancelled',
            'message' => 'This login request has been cancelled<br>Cannot be scanned again',
            'hint' => 'Please generate a new QR code on your computer',
        ],
        'token_expired' => [
            'title' => 'QR Code Expired',
            'message' => 'This QR code has expired<br>Please do not proceed with login',
            'hint' => 'Please refresh the page on your computer to generate a new QR code',
        ],
        'token_claimed' => [
            'title' => 'QR Code Claimed by Another Device',
            'message' => 'This login request has been opened on another device<br>Please verify if this was you',
            'hint' => 'If this was not you, please refresh the QR code on your computer',
        ],
        'rate_limit_exceeded' => [
            'title' => 'Too Many Attempts',
            'message' => 'You are making requests too frequently<br>Please try again later',
            'hint' => 'For your account security, we have rate limited this action',
        ],
    ],

    // Device information
    'device_info' => [
        'location' => 'Login Location',
        'device' => 'Device',
        'system' => 'System',
        'browser' => 'Browser',
        'unknown' => 'Unknown',
        'unknown_device' => 'Unknown Device',
        'unknown_system' => 'Unknown System',
        'unknown_browser' => 'Unknown Browser',
    ],

    // Security notice
    'security_notice' => 'Security Notice: Please carefully verify the login location, device, system, and browser information. Only confirm if you recognize this login attempt. Click cancel if you have any doubts.',

    // Instructions
    'instructions' => [
        'step1' => 'Open the app on your mobile device and login',
        'step2' => 'Scan the QR code above',
        'step3' => 'Confirm the login on your mobile device',
    ],

    // Hints
    'hints' => [
        'qr_code_expires' => 'QR code will expire in :minutes minutes',
        'page_can_close' => 'This page can be safely closed',
    ],

    // Error messages
    'errors' => [
        'token_not_found' => 'Login token not found',
        'token_invalid' => 'Invalid login token',
        'token_unavailable' => 'Login token unavailable',
        'rate_limit' => 'Too many attempts, please try again later',
        'unauthorized' => 'Unauthorized access',
    ],
];
