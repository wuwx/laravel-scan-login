<?php

namespace Wuwx\LaravelScanLogin;

use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wuwx\LaravelScanLogin\Console\Commands\CleanupExpiredTokensCommand;
use Wuwx\LaravelScanLogin\Console\Commands\TokenStatisticsCommand;
use Wuwx\LaravelScanLogin\Livewire\Pages\MobileLoginConfirmPage;
use Wuwx\LaravelScanLogin\Livewire\Pages\QrCodeLoginPage;
use Wuwx\LaravelScanLogin\Services\GeoLocationService;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;

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
            ->hasRoute('web')
            ->hasCommands([
                CleanupExpiredTokensCommand::class,
                TokenStatisticsCommand::class,
            ]);
    }


    public function packageBooted(): void
    {
        // 注册事件监听器
        $this->registerEventListeners();
    }

    public function packageRegistered(): void
    {
        // Register the service as singleton
        $this->app->singleton(ScanLoginTokenService::class);
        $this->app->singleton(GeoLocationService::class);
        $this->app->singleton(\Wuwx\LaravelScanLogin\Services\RateLimitService::class);
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        // 如果用户想要自定义监听器，可以在配置中禁用默认监听器
        if (!config('scan-login.enable_default_listeners', true)) {
            return;
        }

        // 注册日志监听器
        $events = $this->app['events'];
        $events->subscribe(\Wuwx\LaravelScanLogin\Listeners\LogScanLoginActivity::class);
    }


}
