<?php

namespace Wuwx\LaravelScanLogin;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Wuwx\LaravelScanLogin\Livewire\QrCodeLogin;
use Wuwx\LaravelScanLogin\Livewire\MobileLoginConfirm;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;

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
            ->hasMigration('create_scan_login_tokens_table')
            ->hasRoute('web');
    }

    public function packageRegistered(): void
    {
        // No services to register - all functionality is in the model
    }

    public function packageBooted(): void
    {
        // Register Livewire components
        \Livewire\Livewire::component('scan-login::qr-code-login', QrCodeLogin::class);
        \Livewire\Livewire::component('scan-login::mobile-login-confirm', MobileLoginConfirm::class);
        
        // Schedule token cleanup using model:prune
        $this->scheduleTokenCleanup();
        
        // Validate configuration on boot
        $this->validateConfiguration();
    }

    /**
     * Schedule token cleanup using model:prune command.
     */
    protected function scheduleTokenCleanup(): void
    {
        if (!config('scan-login.enabled', true)) {
            return;
        }

        // Schedule the model:prune command for ScanLoginToken
        // Run every hour by default, configurable via config
        $schedule = config('scan-login.cleanup_schedule', '0 * * * *'); // Every hour
        
        Schedule::command('model:prune', [
            '--model' => [ScanLoginToken::class],
            '--batch-size' => config('scan-login.cleanup_batch_size', 1000),
        ])
        ->cron($schedule)
        ->name('scan-login-token-cleanup')
        ->withoutOverlapping()
        ->runInBackground();
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