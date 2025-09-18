<?php

use Wuwx\LaravelScanLogin\Services\ScanLoginService;
use Wuwx\LaravelScanLogin\Services\TokenManager;
use Wuwx\LaravelScanLogin\Services\QrCodeGenerator;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Foundation\Auth\User;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create the table manually for testing
    Schema::create('scan_login_tokens', function (Blueprint $table) {
        $table->id();
        $table->string('token')->unique();
        $table->enum('status', ['pending', 'used', 'expired'])->default('pending');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->timestamp('expires_at');
        $table->timestamp('used_at')->nullable();
        $table->timestamps();

        // Indexes for performance
        $table->index('token');
        $table->index('status');
        $table->index('expires_at');
        $table->index(['status', 'expires_at']);
    });
    
    // Register the route for testing
    Route::get('/scan-login/{token}', function ($token) {
        return response()->json(['token' => $token]);
    })->name('scan-login.mobile-login');
    
    // Create mock user provider
    $this->userProvider = Mockery::mock(UserProvider::class);
    
    // Create services
    $this->tokenManager = new TokenManager();
    $this->qrCodeGenerator = new QrCodeGenerator();
    $this->scanLoginService = new ScanLoginService(
        $this->tokenManager,
        $this->qrCodeGenerator,
        $this->userProvider
    );
});

afterEach(function () {
    Mockery::close();
});

it('can generate qr code', function () {
    $result = $this->scanLoginService->generateQrCode();
    
    expect($result['success'])->toBeTrue();
    expect($result['data'])->toHaveKeys(['token', 'qr_code', 'login_url', 'expires_at', 'polling_interval']);
    expect($result['data']['token'])->toBeString();
    expect($result['data']['qr_code'])->toContain('<svg');
    expect($result['data']['login_url'])->toContain('/scan-login/');
    expect($result['data']['polling_interval'])->toBe(3);
});

it('can process successful login', function () {
    $token = $this->tokenManager->create();
    $credentials = [
        'email' => 'test@example.com',
        'password' => 'password123',
    ];
    
    // Mock user
    $user = Mockery::mock(User::class);
    $user->shouldReceive('getAuthIdentifier')->andReturn(123);
    $user->shouldReceive('getAuthIdentifierName')->andReturn('email');
    
    // Mock user provider
    $this->userProvider->shouldReceive('retrieveByCredentials')
        ->with($credentials)
        ->andReturn($user);
    $this->userProvider->shouldReceive('validateCredentials')
        ->with($user, $credentials)
        ->andReturn(true);
    
    $result = $this->scanLoginService->processLogin($token, $credentials);
    
    expect($result['success'])->toBeTrue();
    expect($result['data']['message'])->toBe('登录成功');
    expect($result['data']['user']['id'])->toBe(123);
    
    // Verify token was marked as used
    expect($this->tokenManager->getStatus($token))->toBe('used');
});

it('rejects login with invalid token', function () {
    $credentials = [
        'email' => 'test@example.com',
        'password' => 'password123',
    ];
    
    $result = $this->scanLoginService->processLogin('invalid-token', $credentials);
    
    expect($result['success'])->toBeFalse();
    expect($result['error']['code'])->toBe('INVALID_TOKEN');
    expect($result['error']['message'])->toBe('登录令牌无效或已过期');
});

it('rejects login with invalid credentials', function () {
    $token = $this->tokenManager->create();
    $credentials = [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ];
    
    // Mock user provider to return null (invalid credentials)
    $this->userProvider->shouldReceive('retrieveByCredentials')
        ->with($credentials)
        ->andReturn(null);
    
    $result = $this->scanLoginService->processLogin($token, $credentials);
    
    expect($result['success'])->toBeFalse();
    expect($result['error']['code'])->toBe('INVALID_CREDENTIALS');
    expect($result['error']['message'])->toBe('用户名或密码错误');
});

it('rejects login when password validation fails', function () {
    $token = $this->tokenManager->create();
    $credentials = [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ];
    
    // Mock user
    $user = Mockery::mock(User::class);
    
    // Mock user provider
    $this->userProvider->shouldReceive('retrieveByCredentials')
        ->with($credentials)
        ->andReturn($user);
    $this->userProvider->shouldReceive('validateCredentials')
        ->with($user, $credentials)
        ->andReturn(false);
    
    $result = $this->scanLoginService->processLogin($token, $credentials);
    
    expect($result['success'])->toBeFalse();
    expect($result['error']['code'])->toBe('INVALID_CREDENTIALS');
});

it('can check pending token status', function () {
    $token = $this->tokenManager->create();
    
    $result = $this->scanLoginService->checkLoginStatus($token);
    
    expect($result['success'])->toBeTrue();
    expect($result['data']['status'])->toBe('pending');
    expect($result['data']['message'])->toBe('等待扫码登录');
});

it('can check completed token status', function () {
    $token = $this->tokenManager->create();
    $userId = 123;
    
    // Mark token as used
    $this->tokenManager->markAsUsed($token, $userId);
    
    // Mock user
    $user = Mockery::mock(User::class);
    $user->shouldReceive('getAuthIdentifier')->andReturn($userId);
    $user->shouldReceive('getAuthIdentifierName')->andReturn('email');
    
    // Mock user provider
    $this->userProvider->shouldReceive('retrieveById')
        ->with($userId)
        ->andReturn($user);
    
    $result = $this->scanLoginService->checkLoginStatus($token);
    
    expect($result['success'])->toBeTrue();
    expect($result['data']['status'])->toBe('completed');
    expect($result['data']['message'])->toBe('登录成功');
    expect($result['data']['user']['id'])->toBe($userId);
});

it('can check expired token status', function () {
    $token = $this->tokenManager->create();
    
    // Manually expire the token
    $tokenRecord = ScanLoginToken::where('token', $token)->first();
    $tokenRecord->update(['expires_at' => now()->subMinutes(1)]);
    
    $result = $this->scanLoginService->checkLoginStatus($token);
    
    expect($result['success'])->toBeFalse();
    expect($result['error']['code'])->toBe('TOKEN_EXPIRED');
    expect($result['error']['message'])->toBe('登录令牌已过期，请刷新二维码');
});

it('can check non existent token status', function () {
    $result = $this->scanLoginService->checkLoginStatus('non-existent-token');
    
    expect($result['success'])->toBeFalse();
    expect($result['error']['code'])->toBe('TOKEN_NOT_FOUND');
    expect($result['error']['message'])->toBe('登录令牌不存在');
});

it('can cleanup expired tokens', function () {
    // Create some tokens
    $validToken = $this->tokenManager->create();
    $expiredToken1 = $this->tokenManager->create();
    $expiredToken2 = $this->tokenManager->create();
    
    // Expire some tokens
    ScanLoginToken::whereIn('token', [$expiredToken1, $expiredToken2])
        ->update(['expires_at' => now()->subMinutes(1)]);
    
    $deletedCount = $this->scanLoginService->cleanupExpiredTokens();
    
    expect($deletedCount)->toBe(2);
});

it('can generate qr code with custom options', function () {
    $options = [
        'size' => 150,
        'format' => 'svg',
        'margin' => 2,
    ];
    
    $result = $this->scanLoginService->generateQrCodeWithOptions($options);
    
    expect($result['success'])->toBeTrue();
    expect($result['data'])->toHaveKeys(['token', 'qr_code', 'login_url', 'expires_at', 'polling_interval']);
    expect($result['data']['qr_code'])->toContain('width="150"');
});

it('can generate qr code as png', function () {
    $result = $this->scanLoginService->generateQrCodePng();
    
    expect($result['success'])->toBeTrue();
    expect($result['data'])->toHaveKeys(['token', 'qr_code', 'login_url', 'expires_at', 'polling_interval']);
    expect($result['data']['qr_code'])->toMatch('/^data:image\/(png|svg\+xml);base64,/');
});

it('can check if scan login is enabled', function () {
    config(['scan-login.enabled' => true]);
    expect($this->scanLoginService->isEnabled())->toBeTrue();
    
    config(['scan-login.enabled' => false]);
    expect($this->scanLoginService->isEnabled())->toBeFalse();
});

it('can get configuration', function () {
    config([
        'scan-login.enabled' => true,
        'scan-login.token_expiry_minutes' => 10,
        'scan-login.polling_interval_seconds' => 5,
        'scan-login.qr_code_size' => 300,
        'scan-login.login_success_redirect' => '/home',
    ]);
    
    $config = $this->scanLoginService->getConfig();
    
    expect($config)->toBe([
        'enabled' => true,
        'token_expiry_minutes' => 10,
        'polling_interval_seconds' => 5,
        'qr_code_size' => 300,
        'login_success_redirect' => '/home',
    ]);
});

it('uses default config values when not set', function () {
    // Clear all config values to test defaults
    config([
        'scan-login' => [],
    ]);
    
    $config = $this->scanLoginService->getConfig();
    
    expect($config)->toBe([
        'enabled' => true,
        'token_expiry_minutes' => 5,
        'polling_interval_seconds' => 3,
        'qr_code_size' => 200,
        'login_success_redirect' => '/dashboard',
    ]);
});

it('handles missing email in credentials', function () {
    $token = $this->tokenManager->create();
    $credentials = [
        'password' => 'password123',
        // Missing email
    ];
    
    $result = $this->scanLoginService->processLogin($token, $credentials);
    
    expect($result['success'])->toBeFalse();
    expect($result['error']['code'])->toBe('INVALID_CREDENTIALS');
});

it('handles missing password in credentials', function () {
    $token = $this->tokenManager->create();
    $credentials = [
        'email' => 'test@example.com',
        // Missing password
    ];
    
    // Mock user
    $user = Mockery::mock(User::class);
    
    // Mock user provider
    $this->userProvider->shouldReceive('retrieveByCredentials')
        ->with($credentials)
        ->andReturn($user);
    
    $result = $this->scanLoginService->processLogin($token, $credentials);
    
    expect($result['success'])->toBeFalse();
    expect($result['error']['code'])->toBe('INVALID_CREDENTIALS');
});