<?php

namespace Wuwx\LaravelScanLogin;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wuwx\LaravelScanLogin\Commands\LaravelScanLoginCommand;
use Wuwx\LaravelScanLogin\Commands\ScanLoginPerformanceCommand;
use Wuwx\LaravelScanLogin\Commands\ScanLoginCleanupCommand;
use Wuwx\LaravelScanLogin\Commands\ScanLoginHealthCheckCommand;
use Wuwx\LaravelScanLogin\Commands\ScanLoginMonitoringCommand;
use Wuwx\LaravelScanLogin\Middleware\ValidateTokenMiddleware;
use Wuwx\LaravelScanLogin\Services\ConfigValidator;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

class LaravelScanLoginServiceProvider extends PackageServiceProvider
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
            ->hasCommand(LaravelScanLoginCommand::class)
            ->hasCommand(ScanLoginPerformanceCommand::class)
            ->hasCommand(ScanLoginCleanupCommand::class)
            ->hasCommand(ScanLoginHealthCheckCommand::class)
            ->hasCommand(ScanLoginMonitoringCommand::class)
            ->hasRoute('web')
            ->hasRoute('api');
    }

    public function packageBooted(): void
    {
        // Register middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('scan-login.validate-token', ValidateTokenMiddleware::class);
        
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

        try {
            ConfigValidator::getValidatedConfig();
        } catch (\Exception $e) {
            // Log configuration validation errors but don't break the application
            logger()->warning('Scan login configuration validation failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
