<?php

namespace Wuwx\LaravelScanLogin;

use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
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
            ->hasRoute('web');
    }


    public function packageBooted(): void
    {
        //
    }

    public function packageRegistered(): void
    {
        // Register the service as singleton
        $this->app->singleton(ScanLoginTokenService::class);
        $this->app->singleton(GeoLocationService::class);
    }


}
