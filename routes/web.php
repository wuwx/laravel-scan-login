<?php

use Illuminate\Support\Facades\Route;
use Wuwx\LaravelScanLogin\Controllers\ScanLoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your package. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

Route::prefix('scan-login')
    ->name('scan-login.')
    ->group(function () {
        // Mobile login page - requires valid token
        Route::get('/{token}', [ScanLoginController::class, 'showMobileLogin'])
            ->middleware('scan-login.validate-token')
            ->name('mobile-login');
        
        // Process mobile login - requires valid token
        Route::post('/{token}', [ScanLoginController::class, 'processMobileLogin'])
            ->middleware('scan-login.validate-token')
            ->name('process-mobile-login');
    });