<?php

use Illuminate\Support\Facades\Route;
use Wuwx\LaravelScanLogin\Controllers\ScanLoginController;
use Wuwx\LaravelScanLogin\Controllers\HealthCheckController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your package. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::prefix('scan-login')
    ->name('scan-login.api.')
    ->group(function () {
        // Generate QR code for desktop login
        Route::post('/generate', [ScanLoginController::class, 'generateQrCode'])
            ->name('generate');
        
        // Check login status for polling - no token validation middleware
        // because we need to handle expired/invalid tokens gracefully
        Route::get('/status/{token}', [ScanLoginController::class, 'checkStatus'])
            ->name('status');
        
        // Health check endpoints
        Route::get('/health', [HealthCheckController::class, 'check'])
            ->name('health');
        
        Route::get('/health/liveness', [HealthCheckController::class, 'liveness'])
            ->name('health.liveness');
        
        Route::get('/health/readiness', [HealthCheckController::class, 'readiness'])
            ->name('health.readiness');
    });