<?php

use Illuminate\Routing\RouteCollection;
use Wuwx\LaravelScanLogin\Console\Commands\CleanupExpiredTokensCommand;
use Wuwx\LaravelScanLogin\Console\Commands\TokenStatisticsCommand;
use Wuwx\LaravelScanLogin\Console\Commands\ValidateQrCodeConfigCommand;
use Wuwx\LaravelScanLogin\Services\GeoLocationService;
use Wuwx\LaravelScanLogin\Services\QrCodeService;
use Wuwx\LaravelScanLogin\Services\RateLimitService;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;

it('registers services as singletons', function () {
    $service1 = app(ScanLoginTokenService::class);
    $service2 = app(ScanLoginTokenService::class);
    
    expect($service1)->toBe($service2);
});

it('registers geo location service as singleton', function () {
    $service1 = app(GeoLocationService::class);
    $service2 = app(GeoLocationService::class);
    
    expect($service1)->toBe($service2);
});

it('registers rate limit service as singleton', function () {
    $service1 = app(RateLimitService::class);
    $service2 = app(RateLimitService::class);
    
    expect($service1)->toBe($service2);
});

it('registers qr code service as singleton', function () {
    $service1 = app(QrCodeService::class);
    $service2 = app(QrCodeService::class);
    
    expect($service1)->toBe($service2);
});

it('registers cleanup command', function () {
    expect(app()->make(CleanupExpiredTokensCommand::class))
        ->toBeInstanceOf(CleanupExpiredTokensCommand::class);
});

it('registers statistics command', function () {
    expect(app()->make(TokenStatisticsCommand::class))
        ->toBeInstanceOf(TokenStatisticsCommand::class);
});

it('registers validate qr config command', function () {
    expect(app()->make(ValidateQrCodeConfigCommand::class))
        ->toBeInstanceOf(ValidateQrCodeConfigCommand::class);
});

it('has config file published', function () {
    expect(config('scan-login'))->toBeArray()
        ->toHaveKeys([
            'token_expiry_minutes',
            'token_length',
            'enable_geoip',
            'rate_limit',
            'qr_code',
        ]);
});

it('uses empty default whitelist and blacklist arrays', function () {
    expect(config('scan-login.rate_limit.whitelist'))->toBeArray()->toBeEmpty();
    expect(config('scan-login.rate_limit.blacklist'))->toBeArray()->toBeEmpty();
});

it('has translations loaded', function () {
    expect(__('scan-login::scan-login.page.title'))->toBeString();
});

it('registers event listeners when enabled', function () {
    // Default config has enable_default_listeners = true
    // After app boot, LogScanLoginActivity subscriber should be registered
    $listeners = app('events')->getListeners(\Wuwx\LaravelScanLogin\Events\ScanLoginTokenCreated::class);
    
    expect($listeners)->not->toBeEmpty();
});

it('does not register event listeners when disabled', function () {
    config(['scan-login.enable_default_listeners' => false]);
    
    // When disabled, calling registerEventListeners should not add new listeners
    $provider = new \Wuwx\LaravelScanLogin\ScanLoginServiceProvider(app());
    
    $beforeCount = count(app('events')->getListeners(
        \Wuwx\LaravelScanLogin\Events\ScanLoginTokenCreated::class
    ));
    
    $method = (new ReflectionClass($provider))->getMethod('registerEventListeners');
    $method->setAccessible(true);
    $method->invoke($provider);
    
    $afterCount = count(app('events')->getListeners(
        \Wuwx\LaravelScanLogin\Events\ScanLoginTokenCreated::class
    ));
    
    expect($afterCount)->toBe($beforeCount);
});

it('has views published', function () {
    expect(view()->exists('scan-login::livewire.pages.qr-code-login-page'))->toBeTrue();
});

it('has migration published', function () {
    $migrationPath = database_path('migrations');
    $files = glob($migrationPath . '/*_create_scan_login_tokens_table.php');
    
    // 在测试环境中，迁移可能在 stub 文件中
    expect(
        file_exists(database_path('migrations/create_scan_login_tokens_table.php.stub'))
        || count($files) > 0
    )->toBeTrue();
});

it('does not register scan routes when package is disabled', function () {
    $router = app('router');
    $originalRoutes = $router->getRoutes();

    config(['scan-login.enabled' => false]);
    $router->setRoutes(new RouteCollection());

    require __DIR__ . '/../routes/web.php';

    expect($router->getRoutes()->hasNamedRoute('scan-login.qr-code-page'))->toBeFalse();
    expect($router->getRoutes()->hasNamedRoute('scan-login.mobile-login'))->toBeFalse();

    $router->setRoutes($originalRoutes);
});
