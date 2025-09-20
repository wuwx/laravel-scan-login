<?php

use Illuminate\Support\Facades\Route;
use Wuwx\LaravelScanLogin\Livewire\QrCodeLogin;
use Wuwx\LaravelScanLogin\Livewire\MobileLoginConfirm;

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
    ->middleware(['web'])
    ->group(function () {
        // QR Code display page - Direct Livewire component for desktop login
        Route::get('/', QrCodeLogin::class)
            ->name('qr-code-page');
        
        // Mobile login confirmation page - Direct Livewire component for mobile confirmation
        Route::get('/{token}', MobileLoginConfirm::class)
            ->middleware(['auth'])
            ->name('mobile-login')
            ->where('token', '[a-zA-Z0-9\-_]+');
    });