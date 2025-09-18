<?php

namespace Wuwx\LaravelScanLogin;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wuwx\LaravelScanLogin\Commands\LaravelScanLoginCommand;

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
            ->hasCommand(LaravelScanLoginCommand::class);
    }
}
