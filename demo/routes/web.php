<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DemoController;

/*
|--------------------------------------------------------------------------
| Demo Web Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Laravel Scan Login demo application.
| These routes showcase different implementations and use cases.
|
*/

// Include authentication routes
require __DIR__.'/auth.php';

// Demo home page
Route::get('/', [DemoController::class, 'index'])->name('demo.index');

// Demo scenarios
Route::get('/demo/basic', [DemoController::class, 'basic'])->name('demo.basic');
Route::get('/demo/custom', [DemoController::class, 'custom'])->name('demo.custom');
Route::get('/demo/api', [DemoController::class, 'api'])->name('demo.api');
Route::get('/demo/errors', [DemoController::class, 'errors'])->name('demo.errors');

// Demo API endpoints
Route::post('/demo/generate-qr', [DemoController::class, 'generateQrCode'])->name('demo.generate-qr');
Route::get('/demo/status/{token}', [DemoController::class, 'checkStatus'])->name('demo.check-status');
Route::post('/demo/simulate-error', [DemoController::class, 'simulateError'])->name('demo.simulate-error');

// Demo statistics and controls
Route::get('/demo/statistics', [DemoController::class, 'statistics'])->name('demo.statistics');
Route::post('/demo/reset', [DemoController::class, 'reset'])->name('demo.reset');

// Mobile demo login
Route::get('/demo/mobile/{token}', [DemoController::class, 'showMobileDemo'])->name('demo.mobile-login');
Route::post('/demo/mobile/{token}', [DemoController::class, 'processMobileDemo'])->name('demo.process-mobile-login');

// Protected demo routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::get('/demo/dashboard', [DemoController::class, 'dashboard'])->name('demo.dashboard');
});

// Logout is now handled in routes/auth.php

// Authentication routes are now handled in routes/auth.php

// Fallback route for demo
Route::fallback(function () {
    return redirect()->route('demo.index');
});