<?php

use Illuminate\Support\Facades\Route;
use Wuwx\LaravelScanLogin\Livewire\QrCodePage;
use Wuwx\LaravelScanLogin\Livewire\MobileLoginPage;

/*
|--------------------------------------------------------------------------
| Scan Login Routes
|--------------------------------------------------------------------------
|
| These routes handle the QR code scan login functionality using Livewire
| components for a modern, reactive user experience.
|
*/

Route::prefix('scan-login')
    ->name('scan-login.')
    ->group(function () {
        // QR Code display page - Livewire component for desktop login
        Route::get('/', QrCodePage::class)
            ->name('qr-code-page');
        
        // Mobile login confirmation page - Livewire component for mobile confirmation
        Route::get('/{token}', MobileLoginPage::class)
            ->middleware(['auth', 'scan-login.validate-token'])
            ->name('mobile-login')
            ->where('token', '[a-zA-Z0-9\-_]+');
    });