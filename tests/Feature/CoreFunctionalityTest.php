<?php

use Wuwx\LaravelScanLogin\Tests\Support\TestUser;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\ScanLoginService;
use Wuwx\LaravelScanLogin\Services\TokenManager;
use Wuwx\LaravelScanLogin\Services\QrCodeGenerator;

beforeEach(function () {
    // Create a test user
    $this->user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
});

it('can create and validate tokens', function () {
    $tokenManager = app(TokenManager::class);
    
    // Create a token
    $token = $tokenManager->create();
    expect($token)->not->toBeEmpty();
    expect(strlen($token))->toBeGreaterThan(32);
    
    // Validate the token
    $isValid = $tokenManager->validate($token);
    expect($isValid)->toBeTrue();
    
    // Check token status
    $status = $tokenManager->getStatus($token);
    expect($status)->toBe('pending');
});

it('can mark tokens as used', function () {
    $tokenManager = app(TokenManager::class);
    
    $token = $tokenManager->create();
    
    // Mark as used
    $tokenManager->markAsUsed($token, $this->user->id);
    
    // Check status
    $status = $tokenManager->getStatus($token);
    expect($status)->toBe('used');
    
    // Get user ID
    $userId = $tokenManager->getUserId($token);
    expect($userId)->toBe($this->user->id);
});

it('can generate qr codes', function () {
    $qrGenerator = app(QrCodeGenerator::class);
    
    $token = 'test-token-123';
    $qrCode = $qrGenerator->generate($token);
    
    expect($qrCode)->not->toBeEmpty();
    expect($qrCode)->toContain('svg');
});

it('can process login flow', function () {
    $scanLoginService = app(ScanLoginService::class);
    
    // Generate QR code
    $result = $scanLoginService->generateQrCode();
    expect($result['success'])->toBeTrue();
    expect($result['data']['token'])->not->toBeEmpty();
    expect($result['data']['qr_code'])->not->toBeEmpty();
    
    $token = $result['data']['token'];
    
    // Process login
    $loginResult = $scanLoginService->processLogin($token, $this->user);
    expect($loginResult['success'])->toBeTrue();
    
    // Check login status
    $statusResult = $scanLoginService->checkLoginStatus($token);
    expect($statusResult['success'])->toBeTrue();
    expect($statusResult['data']['status'])->toBe('completed');
});

it('handles token expiration', function () {
    $tokenManager = app(TokenManager::class);
    
    // Create an expired token
    $token = ScanLoginToken::create([
        'token' => 'expired-token-123',
        'status' => 'pending',
        'expires_at' => now()->subMinutes(1),
    ]);
    
    $status = $tokenManager->getStatus($token->token);
    expect($status)->toBe('expired');
});

it('can cleanup expired tokens', function () {
    $tokenManager = app(TokenManager::class);
    
    // Create some expired tokens
    ScanLoginToken::create([
        'token' => 'expired-1',
        'status' => 'pending',
        'expires_at' => now()->subMinutes(10),
    ]);
    
    ScanLoginToken::create([
        'token' => 'expired-2',
        'status' => 'pending',
        'expires_at' => now()->subMinutes(5),
    ]);
    
    $initialCount = ScanLoginToken::count();
    expect($initialCount)->toBeGreaterThanOrEqual(2);
    
    // Cleanup expired tokens
    $cleaned = $tokenManager->cleanup();
    expect($cleaned)->toBeGreaterThan(0);
    
    $finalCount = ScanLoginToken::count();
    expect($finalCount)->toBeLessThan($initialCount);
});

it('validates scan login configuration', function () {
    $scanLoginService = app(ScanLoginService::class);
    
    expect($scanLoginService->isEnabled())->toBeTrue();
    
    $config = $scanLoginService->getConfig();
    expect($config)->toBeArray();
    expect($config['enabled'])->toBeTrue();
    expect($config['token_expiry_minutes'])->toBeGreaterThan(0);
});

it('handles invalid tokens gracefully', function () {
    $scanLoginService = app(ScanLoginService::class);
    
    $result = $scanLoginService->processLogin('invalid-token', $this->user);
    expect($result['success'])->toBeFalse();
    expect($result['error']['code'])->toBe('INVALID_TOKEN');
});

it('can cancel tokens', function () {
    $tokenManager = app(TokenManager::class);
    
    $token = $tokenManager->create();
    
    // Cancel the token
    $cancelled = $tokenManager->cancel($token);
    expect($cancelled)->toBeTrue();
    
    // Check that token is cancelled
    $tokenRecord = ScanLoginToken::where('token', $token)->first();
    expect($tokenRecord->status)->toBe('cancelled');
});