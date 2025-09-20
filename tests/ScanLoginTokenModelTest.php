<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Table is already created by TestCase migration
});

it('can create a scan login token', function () {
    $token = ScanLoginToken::create([
        'token' => 'test-token-123',
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
        'expires_at' => now()->addMinutes(5),
    ]);

    expect($token->token)->toBe('test-token-123');
    expect($token->status)->toBeInstanceOf(ScanLoginTokenStatePending::class);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => 'test-token-123',
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
    ]);
});



it('can mark token as used', function () {
    $token = ScanLoginToken::create([
        'token' => 'test-token',
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
        'expires_at' => now()->addMinutes(5),
    ]);

    $userId = 123;
    $token->markAsConsumed($userId);

    $token->refresh();

    expect($token->status)->toBeInstanceOf(ScanLoginTokenStateConsumed::class);
    expect($token->consumer_id)->toBe($userId);
    expect($token->consumed_at)->not->toBeNull();
});

it('can mark token as expired', function () {
    $token = ScanLoginToken::create([
        'token' => 'test-token',
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Test that token can be updated directly
    $token->update(['status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired']);

    $token->refresh();

    expect($token->status)->toBeInstanceOf(ScanLoginTokenStateExpired::class);
});

it('can scope pending tokens', function () {
    // Create pending valid token
    ScanLoginToken::create([
        'token' => 'pending-valid',
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Create pending expired token
    ScanLoginToken::create([
        'token' => 'pending-expired',
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
        'expires_at' => now()->subMinutes(1),
    ]);

    // Create used token
    ScanLoginToken::create([
        'token' => 'used-token',
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed',
        'expires_at' => now()->addMinutes(5),
    ]);

    $pendingTokens = ScanLoginToken::issued()->get();

    expect($pendingTokens)->toHaveCount(1);
    expect($pendingTokens->first()->token)->toBe('pending-valid');
});

it('can scope expired tokens', function () {
    // Create expired token with expired status
    ScanLoginToken::create([
        'token' => 'expired-status',
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Create token with past expiry date
    ScanLoginToken::create([
        'token' => 'expired-time',
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
        'expires_at' => now()->subMinutes(1),
    ]);

    // Create valid token
    ScanLoginToken::create([
        'token' => 'valid-token',
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
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
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
        'expires_at' => now()->addMinutes(5),
        'consumed_at' => now(),
    ]);

    expect($token->expires_at)->toBeInstanceOf(Carbon::class);
    expect($token->consumed_at)->toBeInstanceOf(Carbon::class);
});

it('has correct fillable attributes', function () {
    $token = new ScanLoginToken();
    
    $expectedFillable = [
        'token',
        'status',
        'claimer_id',
        'consumer_id',
        'expires_at',
        'claimed_at',
        'consumed_at',
        'cancelled_at',
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
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
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
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
        'expires_at' => now()->addMinutes(5),
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
    ]);

    // Test that device info is stored correctly
    expect($token->ip_address)->toBe('192.168.1.100');
    expect($token->user_agent)->toBe('Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)');
});