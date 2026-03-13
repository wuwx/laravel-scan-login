<?php

namespace Wuwx\LaravelScanLogin;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wuwx\LaravelScanLogin\Console\Commands\CleanupExpiredTokensCommand;
use Wuwx\LaravelScanLogin\Console\Commands\TokenStatisticsCommand;
use Wuwx\LaravelScanLogin\Console\Commands\ValidateQrCodeConfigCommand;
use Wuwx\LaravelScanLogin\Livewire\QrCodeLogin;
use Wuwx\LaravelScanLogin\Livewire\QrCodeLoginModal;
use Wuwx\LaravelScanLogin\Listeners\LogScanLoginActivity;
use Wuwx\LaravelScanLogin\Services\GeoLocationService;
use Wuwx\LaravelScanLogin\Services\QrCodeService;
use Wuwx\LaravelScanLogin\Services\RateLimitService;
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
            ->hasTranslations()
            ->hasMigration('create_scan_login_tokens_table')
            ->hasRoute('web')
            ->hasCommands([
                CleanupExpiredTokensCommand::class,
                TokenStatisticsCommand::class,
                ValidateQrCodeConfigCommand::class,
            ]);
    }


    public function packageBooted(): void
    {
        // 注册事件监听器
        $this->registerEventListeners();

        // 注册可嵌入的 Livewire 组件（供宿主应用在视图中直接使用）
        \Livewire\Livewire::component('scan-login.qr-code-login', QrCodeLogin::class);
        \Livewire\Livewire::component('scan-login.qr-code-login-modal', QrCodeLoginModal::class);
    }

    public function packageRegistered(): void
    {
        // Register core services as singletons.
        $this->app->singleton(ScanLoginTokenService::class);
        $this->app->singleton(GeoLocationService::class);
        $this->app->singleton(RateLimitService::class);
        $this->app->singleton(QrCodeService::class);
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
        $events->subscribe(LogScanLoginActivity::class);
    }


}
