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
            ->hasRoute('web');
    }


    public function packageBooted(): void
    {
        // Register Livewire components
        \Livewire\Livewire::component('scan-login::qr-code-login', QrCodeLogin::class);
        \Livewire\Livewire::component('scan-login::mobile-login-confirm', MobileLoginConfirm::class);
        
    }

    public function packageRegistered(): void
    {
        // Register the service as singleton
        $this->app->singleton(ScanLoginTokenService::class);
    }


}