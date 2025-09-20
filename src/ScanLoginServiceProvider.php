<?php

namespace Wuwx\LaravelScanLogin;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wuwx\LaravelScanLogin\Commands\ScanLoginCleanupCommand;
use Wuwx\LaravelScanLogin\Middleware\ValidateTokenMiddleware;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

class ScanLoginServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-scan-login')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_scan_login_table')
            ->hasCommand(ScanLoginCleanupCommand::class)
            ->hasRoute('web');
    }

    public function packageRegistered(): void
    {
        // Register core services
        $this->app->bind(\Wuwx\LaravelScanLogin\Services\TokenManager::class);
        $this->app->bind(\Wuwx\LaravelScanLogin\Services\QrCodeGenerator::class);
    }

    public function packageBooted(): void
    {
        // Register middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('scan-login.validate-token', ValidateTokenMiddleware::class);
        
        // Register Livewire components
        \Livewire\Livewire::component('scan-login::qr-code-page', \Wuwx\LaravelScanLogin\Livewire\QrCodePage::class);
        \Livewire\Livewire::component('scan-login::mobile-login-page', \Wuwx\LaravelScanLogin\Livewire\MobileLoginPage::class);
        \Livewire\Livewire::component('scan-login::qr-code-login', \Wuwx\LaravelScanLogin\Livewire\QrCodeLogin::class);
        \Livewire\Livewire::component('scan-login::mobile-login-confirm', \Wuwx\LaravelScanLogin\Livewire\MobileLoginConfirm::class);
        
        // Validate configuration on boot
        $this->validateConfiguration();
    }

    /**
     * Validate the package configuration.
     */
    protected function validateConfiguration(): void
    {
        if ($this->app->runningInConsole()) {
            return; // Skip validation during console commands like config:cache
        }

        // Basic configuration validation
        if (!config('scan-login.enabled', true)) {
            return;
        }

        // Log warning if required configuration is missing
        $requiredConfigs = ['token_expiry_minutes', 'qr_code_size'];
        foreach ($requiredConfigs as $config) {
            if (!config("scan-login.{$config}")) {
                logger()->warning("Scan login configuration '{$config}' is not set, using default value");
            }
        }
    }
}