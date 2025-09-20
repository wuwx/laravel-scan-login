# Laravel Scan Login

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wuwx/laravel-scan-login.svg?style=flat-square)](https://packagist.org/packages/wuwx/laravel-scan-login)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/wuwx/laravel-scan-login/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/wuwx/laravel-scan-login/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/wuwx/laravel-scan-login/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/wuwx/laravel-scan-login/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/wuwx/laravel-scan-login.svg?style=flat-square)](https://packagist.org/packages/wuwx/laravel-scan-login)

Laravel Scan Login is a comprehensive package that enables QR code-based authentication for Laravel applications. Users can scan a QR code displayed on desktop browsers with their mobile devices, enter their credentials on the mobile interface, and automatically log in to the desktop session. This provides a seamless and secure login experience, particularly useful for scenarios like WeChat service account logins or any situation where typing passwords on desktop is inconvenient.

The package features secure token-based authentication, automatic session synchronization, configurable security settings, and a responsive mobile interface. It's designed with security best practices including HTTPS enforcement, rate limiting, token expiration, and comprehensive logging.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-scan-login.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-scan-login)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Requirements

- PHP 8.4+
- Laravel 11.0+ or 12.0+
- HTTPS (required for production)

## Installation

### Step 1: Install the Package

Install the package via Composer:

```bash
composer require wuwx/laravel-scan-login
```

### Step 2: Publish and Run Migrations

Publish the migration files and run them to create the necessary database tables:

```bash
php artisan vendor:publish --tag="laravel-scan-login-migrations"
php artisan migrate
```

### Step 3: Publish Configuration

Publish the configuration file to customize the package settings:

```bash
php artisan vendor:publish --tag="laravel-scan-login-config"
```

### Step 4: Publish Views (Optional)

If you want to customize the login views, publish them:

```bash
php artisan vendor:publish --tag="laravel-scan-login-views"
```

### Step 5: Configure Environment Variables

Add the following environment variables to your `.env` file:

```env
# Basic Configuration
SCAN_LOGIN_ENABLED=true
SCAN_LOGIN_TOKEN_EXPIRY_MINUTES=5
SCAN_LOGIN_SUCCESS_REDIRECT=/dashboard

# Security Configuration (Production)
SCAN_LOGIN_REQUIRE_HTTPS=true
SCAN_LOGIN_RATE_LIMIT_PER_MINUTE=10

# UI Configuration
SCAN_LOGIN_POLLING_INTERVAL_SECONDS=3
SCAN_LOGIN_QR_CODE_SIZE=200

# Layout Customization (Optional)
SCAN_LOGIN_LAYOUT_VIEW=scan-login::layouts.app
SCAN_LOGIN_MOBILE_LAYOUT_VIEW=scan-login::layouts.mobile
```

## Architecture

This package is built with **Livewire 3** for a modern, reactive user experience:

- **Server-side state management** - No complex JavaScript required
- **Real-time updates** - Automatic polling and status synchronization  
- **Secure by default** - Built-in CSRF protection and validation
- **Mobile-optimized** - Responsive design for all devices
- **Customizable** - Easy to theme and integrate with your app

The package includes Livewire as a dependency and uses Livewire components for all user interfaces.

## Quick Start

After installation, users can immediately access `/scan-login` to see the QR code page.

### Add to Your App

Add a scan login link to your navigation or login page:

```blade
<a href="{{ route('scan-login.qr-code-page') }}" class="btn btn-primary">
    <i class="fas fa-qrcode"></i> Scan to Login
</a>
```

### How It Works

1. **Desktop**: User visits `/scan-login` and sees a QR code
2. **Mobile**: User scans QR code with their logged-in mobile device  
3. **Confirm**: User confirms login on mobile device
4. **Success**: Desktop automatically logs in and redirects

For detailed usage instructions, see [USAGE_GUIDE.md](USAGE_GUIDE.md).

## Documentation

- 📖 [Usage Guide](USAGE_GUIDE.md) - Complete usage instructions
- ⚡ [Livewire Architecture](LIVEWIRE_INTEGRATION.md) - Technical architecture details  
- 🔄 [Migration Guide](MIGRATION_GUIDE.md) - Upgrading from previous versions

### Option 2: Custom Implementation

If you need more control, you can implement your own QR code display:

#### Basic Implementation

1. **Add QR Code to Your Login Page**

In your login Blade template, include the QR code component:

```blade
@extends('layouts.app')

@section('content')
<div class="login-container">
    <div class="traditional-login">
        <h2>Login with Email & Password</h2>
        <!-- Your existing login form -->
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
    
    <div class="scan-login-divider">
        <span>OR</span>
    </div>
    
    <div class="scan-login">
        <h2>Scan to Login</h2>
        @include('scan-login::qr-code')
    </div>
</div>
@endsection
```

2. **Include Required Assets**

Add the CSS and JavaScript files to your layout:

```blade
@push('styles')
    <link href="{{ asset('vendor/scan-login/css/qr-code-component.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('vendor/scan-login/js/qr-code-component.js') }}"></script>
@endpush
```

3. **Initialize the Component**

Initialize the QR code component in your JavaScript:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.qr-code-container')) {
        new QrCodeComponent({
            container: '.qr-code-container',
            pollingInterval: 3000, // 3 seconds
            onLoginSuccess: function(data) {
                window.location.href = data.redirect_url || '/dashboard';
            },
            onError: function(error) {
                console.error('Scan login error:', error);
            }
        });
    }
});
```

### Advanced Usage

#### Custom Authentication Logic

You can customize the authentication process by extending the `ScanLoginService`:

```php
<?php

namespace App\Services;

use Wuwx\LaravelScanLogin\Services\ScanLoginService;
use Illuminate\Http\Request;

class CustomScanLoginService extends ScanLoginService
{
    public function processLogin(string $token, array $credentials): bool
    {
        // Add custom validation logic
        if (!$this->validateCustomRules($credentials)) {
            return false;
        }
        
        // Call parent method for standard processing
        return parent::processLogin($token, $credentials);
    }
    
    protected function validateCustomRules(array $credentials): bool
    {
        // Your custom validation logic here
        return true;
    }
}
```

Register your custom service in a service provider:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Wuwx\LaravelScanLogin\Services\ScanLoginService;
use App\Services\CustomScanLoginService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ScanLoginService::class, CustomScanLoginService::class);
    }
}
```

#### Custom Views

Create custom views by copying the published views and modifying them:

```bash
php artisan vendor:publish --tag="laravel-scan-login-views"
```

Then customize `resources/views/vendor/scan-login/mobile-login.blade.php`:

```blade
@extends('layouts.mobile')

@section('content')
<div class="mobile-login-container">
    <div class="brand-header">
        <img src="{{ asset('images/logo.png') }}" alt="Logo">
        <h1>{{ config('app.name') }}</h1>
    </div>
    
    <form id="mobile-login-form" class="mobile-login-form">
        @csrf
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="login-button">
            <span class="button-text">Login</span>
            <span class="loading-spinner" style="display: none;">...</span>
        </button>
    </form>
    
    <div class="error-message" style="display: none;"></div>
    <div class="success-message" style="display: none;"></div>
</div>
@endsection
```

#### API Integration

Use the scan login service programmatically:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Wuwx\LaravelScanLogin\Services\ScanLoginService;
use Wuwx\LaravelScanLogin\Facades\ScanLogin;

class CustomLoginController extends Controller
{
    public function generateCustomQrCode(Request $request)
    {
        $scanLoginService = app(ScanLoginService::class);
        
        try {
            $result = $scanLoginService->generateQrCode();
            
            return response()->json([
                'success' => true,
                'qr_code' => $result['qr_code'],
                'token' => $result['token'],
                'expires_at' => $result['expires_at']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate QR code'
            ], 500);
        }
    }
    
    public function checkCustomStatus(string $token)
    {
        $status = ScanLogin::checkLoginStatus($token);
        
        return response()->json($status);
    }
}
```

## Configuration

The package provides extensive configuration options. Here are the key settings:

### Security Settings

```php
// config/scan-login.php
return [
    // Enable/disable the feature
    'enabled' => env('SCAN_LOGIN_ENABLED', true),
    
    // Token security
    'token_expiry_minutes' => env('SCAN_LOGIN_TOKEN_EXPIRY_MINUTES', 5),
    'token_length' => env('SCAN_LOGIN_TOKEN_LENGTH', 64),
    
    // HTTPS enforcement
    'require_https' => env('SCAN_LOGIN_REQUIRE_HTTPS', app()->environment('production')),
    
    // Rate limiting
    'rate_limit_per_minute' => env('SCAN_LOGIN_RATE_LIMIT_PER_MINUTE', 10),
    'max_login_attempts' => env('SCAN_LOGIN_MAX_LOGIN_ATTEMPTS', 3),
];
```

### UI Customization

```php
return [
    // Polling behavior
    'polling_interval_seconds' => env('SCAN_LOGIN_POLLING_INTERVAL_SECONDS', 3),
    'max_polling_duration_minutes' => env('SCAN_LOGIN_MAX_POLLING_DURATION_MINUTES', 10),
    
    // QR code appearance
    'qr_code_size' => env('SCAN_LOGIN_QR_CODE_SIZE', 200),
    'qr_code_error_correction' => env('SCAN_LOGIN_QR_CODE_ERROR_CORRECTION', 'M'),
    
    // Redirect after login
    'login_success_redirect' => env('SCAN_LOGIN_SUCCESS_REDIRECT', '/dashboard'),
];
```

### Performance Settings

```php
return [
    // Token cleanup
    'cleanup_expired_tokens' => env('SCAN_LOGIN_CLEANUP_EXPIRED_TOKENS', true),
    'cleanup_interval_hours' => env('SCAN_LOGIN_CLEANUP_INTERVAL_HOURS', 24),
    'cleanup_batch_size' => env('SCAN_LOGIN_CLEANUP_BATCH_SIZE', 1000),
];
```

## Commands

### Clean Up Expired Tokens

Manually clean up expired tokens:

```bash
php artisan scan-login:cleanup
```

Add this to your scheduler in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('scan-login:cleanup')
             ->daily()
             ->withoutOverlapping();
}
```

## Security Best Practices

1. **Always use HTTPS in production**:
   ```env
   SCAN_LOGIN_REQUIRE_HTTPS=true
   ```

2. **Configure appropriate rate limiting**:
   ```env
   SCAN_LOGIN_RATE_LIMIT_PER_MINUTE=10
   ```

3. **Set reasonable token expiry times**:
   ```env
   SCAN_LOGIN_TOKEN_EXPIRY_MINUTES=5
   ```

4. **Enable logging for security monitoring**:
   ```env
   SCAN_LOGIN_ENABLE_LOGGING=true
   SCAN_LOGIN_LOG_FAILED_ATTEMPTS=true
   ```

## Troubleshooting

### Quick Diagnostics

If you encounter issues, visit `/scan-login/api/diagnose` or use the diagnostic button on the QR code page to get detailed system information.

### Common Issues

**QR Code Not Displaying**
- Ensure the SimpleSoftwareIO/simple-qrcode package is installed
- Check that the routes are properly registered  
- Verify database migrations have been run
- Check Laravel logs for detailed error messages

For detailed troubleshooting steps, see [TROUBLESHOOTING.md](TROUBLESHOOTING.md).

**Mobile Login Not Working**
- Check CSRF token configuration
- Verify middleware is properly applied
- Ensure database migrations have been run

**Polling Not Working**
- Check JavaScript console for errors
- Verify API endpoints are accessible
- Check rate limiting configuration

### Debug Mode

Enable debug logging by setting:

```env
SCAN_LOGIN_ENABLE_LOGGING=true
LOG_LEVEL=debug
```

Check the logs for detailed information:

```bash
tail -f storage/logs/laravel.log | grep scan-login
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [wuwx](https://github.com/wuwx)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
