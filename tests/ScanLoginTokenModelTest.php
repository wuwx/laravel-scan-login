<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Table is already created by TestCase migration
});

it('can create a scan login token', function () {
    $token = ScanLoginToken::create([
        'token' => 'test-token-123',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    expect($token->token)->toBe('test-token-123');
    expect($token->status)->toBe('pending');
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => 'test-token-123',
        'status' => 'pending',
    ]);
});

it('can check if token is expired', function () {
    // Create an expired token
    $expiredToken = ScanLoginToken::create([
        'token' => 'expired-token',
        'status' => 'pending',
        'expires_at' => now()->subMinutes(1),
    ]);

    // Create a valid token
    $validToken = ScanLoginToken::create([
        'token' => 'valid-token',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    expect($expiredToken->isExpired())->toBeTrue();
    expect($validToken->isExpired())->toBeFalse();
});

it('can check if token is pending', function () {
    // Create a pending token that's not expired
    $pendingToken = ScanLoginToken::create([
        'token' => 'pending-token',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Create a used token
    $usedToken = ScanLoginToken::create([
        'token' => 'used-token',
        'status' => 'used',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Create an expired pending token
    $expiredPendingToken = ScanLoginToken::create([
        'token' => 'expired-pending-token',
        'status' => 'pending',
        'expires_at' => now()->subMinutes(1),
    ]);

    expect($pendingToken->isPending())->toBeTrue();
    expect($usedToken->isPending())->toBeFalse();
    expect($expiredPendingToken->isPending())->toBeFalse();
});

it('can mark token as used', function () {
    $token = ScanLoginToken::create([
        'token' => 'test-token',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    $userId = 123;
    $token->markAsUsed($userId);

    $token->refresh();

    expect($token->status)->toBe('used');
    expect($token->user_id)->toBe($userId);
    expect($token->used_at)->not->toBeNull();
});

it('can mark token as expired', function () {
    $token = ScanLoginToken::create([
        'token' => 'test-token',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    $token->markAsExpired();

    $token->refresh();

    expect($token->status)->toBe('expired');
});

it('can scope pending tokens', function () {
    // Create pending valid token
    ScanLoginToken::create([
        'token' => 'pending-valid',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Create pending expired token
    ScanLoginToken::create([
        'token' => 'pending-expired',
        'status' => 'pending',
        'expires_at' => now()->subMinutes(1),
    ]);

    // Create used token
    ScanLoginToken::create([
        'token' => 'used-token',
        'status' => 'used',
        'expires_at' => now()->addMinutes(5),
    ]);

    $pendingTokens = ScanLoginToken::pending()->get();

    expect($pendingTokens)->toHaveCount(1);
    expect($pendingTokens->first()->token)->toBe('pending-valid');
});

it('can scope expired tokens', function () {
    // Create expired token with expired status
    ScanLoginToken::create([
        'token' => 'expired-status',
        'status' => 'expired',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Create token with past expiry date
    ScanLoginToken::create([
        'token' => 'expired-time',
        'status' => 'pending',
        'expires_at' => now()->subMinutes(1),
    ]);

    // Create valid token
    ScanLoginToken::create([
        'token' => 'valid-token',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    $expiredTokens = ScanLoginToken::expired()->get();

    expect($expiredTokens)->toHaveCount(2);
    expect($expiredTokens->pluck('token'))->toContain('expired-status');
    expect($expiredTokens->pluck('token'))->toContain('expired-time');
});

it('casts dates properly', function () {
    $token = ScanLoginToken::create([
        'token' => 'test-token',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
        'used_at' => now(),
    ]);

    expect($token->expires_at)->toBeInstanceOf(Carbon::class);
    expect($token->used_at)->toBeInstanceOf(Carbon::class);
});

it('has correct fillable attributes', function () {
    $token = new ScanLoginToken();
    
    $expectedFillable = [
        'token',
        'status',
        'user_id',
        'expires_at',
        'used_at',
        // 生成二维码时的设备信息
        'ip_address',
        'user_agent',
    ];

    expect($token->getFillable())->toBe($expectedFillable);
});

it('uses correct table name', function () {
    $token = new ScanLoginToken();
    
    expect($token->getTable())->toBe('scan_login_tokens');
});

it('can create token with device information', function () {
    $token = ScanLoginToken::create([
        'token' => 'test-token-with-device',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);

    expect($token->ip_address)->toBe('192.168.1.100');
    expect($token->user_agent)->toBe('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
});

it('can get device info', function () {
    $token = ScanLoginToken::create([
        'token' => 'device-info-token',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
    ]);

    $deviceInfo = $token->getDeviceInfo();

    expect($deviceInfo)->toBe([
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
    ]);
});