<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Wuwx\LaravelScanLogin\Http\Middleware\ScanLoginRateLimiter;

beforeEach(function () {
    config([
        'scan-login.rate_limit.enabled' => true,
        'scan-login.rate_limit.max_attempts' => 3,
        'scan-login.rate_limit.decay_minutes' => 1,
    ]);
});

it('allows requests within rate limit', function () {
    Route::get('/test-rate-limit', function () {
        return response()->json(['success' => true]);
    })->middleware(ScanLoginRateLimiter::class);
    
    // First 3 requests should succeed
    for ($i = 0; $i < 3; $i++) {
        $response = $this->get('/test-rate-limit');
        $response->assertStatus(200);
    }
});

it('blocks requests exceeding rate limit', function () {
    Route::get('/test-rate-limit', function () {
        return response()->json(['success' => true]);
    })->middleware(ScanLoginRateLimiter::class);
    
    // First 3 requests succeed
    for ($i = 0; $i < 3; $i++) {
        $this->get('/test-rate-limit');
    }
    
    // 4th request should be blocked
    $response = $this->get('/test-rate-limit');
    $response->assertStatus(429);
});

it('adds rate limit headers to response', function () {
    Route::get('/test-rate-limit', function () {
        return response()->json(['success' => true]);
    })->middleware(ScanLoginRateLimiter::class);
    
    $response = $this->get('/test-rate-limit');
    
    $response->assertHeader('X-RateLimit-Limit');
    $response->assertHeader('X-RateLimit-Remaining');
});

it('returns retry-after header when rate limited', function () {
    Route::get('/test-rate-limit', function () {
        return response()->json(['success' => true]);
    })->middleware(ScanLoginRateLimiter::class);
    
    // Exceed rate limit
    for ($i = 0; $i < 4; $i++) {
        $response = $this->get('/test-rate-limit');
    }
    
    $response->assertHeader('Retry-After');
});

it('uses different keys for different routes', function () {
    Route::get('/test-qr-code', function () {
        return response()->json(['success' => true]);
    })->name('scan-login.qr-code-page')->middleware(ScanLoginRateLimiter::class);
    
    Route::get('/test-mobile', function () {
        return response()->json(['success' => true]);
    })->name('scan-login.mobile-login')->middleware(ScanLoginRateLimiter::class);
    
    // Requests to different routes should have separate limits
    for ($i = 0; $i < 3; $i++) {
        $this->get('/test-qr-code')->assertStatus(200);
        $this->get('/test-mobile')->assertStatus(200);
    }
});
