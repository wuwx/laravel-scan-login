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
    $token = new ScanLoginToken();
    $token->forceFill([
        'token' => 'test-token-123',
        'state' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);
    $token->save();

    expect($token->token)->toBe('test-token-123');
    expect($token->state)->toBeInstanceOf(ScanLoginTokenStatePending::class);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => 'test-token-123',
        'state' => 'pending',
    ]);
});



it('can mark token as used', function () {
    $token = new ScanLoginToken();
    $token->forceFill([
        'token' => 'test-token',
        'state' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);
    $token->save();

    $userId = 123;
    $claimerId = 456;
    
    // First claim the token
    $token->state->transitionTo(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed::class);
    $token->claimer_id = $claimerId;
    $token->claimed_at = now();
    $token->save();
    
    // Then consume it
    $token->state->transitionTo(ScanLoginTokenStateConsumed::class);
    $token->consumer_id = $userId;
    $token->consumed_at = now();
    $token->save();

    $token->refresh();

    expect($token->state)->toBeInstanceOf(ScanLoginTokenStateConsumed::class);
    expect($token->consumer_id)->toBe($userId);
    expect($token->consumed_at)->not->toBeNull();
});

it('can mark token as expired', function () {
    $token = new ScanLoginToken();
    $token->forceFill([
        'token' => 'test-token',
        'state' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);
    $token->save();

    // Test that token can be updated directly
    $token->state = 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired';
    $token->save();

    $token->refresh();

    expect($token->state)->toBeInstanceOf(ScanLoginTokenStateExpired::class);
});

it('can scope pending tokens', function () {
    // Create pending valid token
    $token1 = new ScanLoginToken();
    $token1->forceFill([
        'token' => 'pending-valid',
        'state' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);
    $token1->save();

    // Create pending expired token
    $token2 = new ScanLoginToken();
    $token2->forceFill([
        'token' => 'pending-expired',
        'state' => 'pending',
        'expires_at' => now()->subMinutes(1),
    ]);
    $token2->save();

    // Create used token
    $token3 = new ScanLoginToken();
    $token3->forceFill([
        'token' => 'used-token',
        'state' => 'consumed',
        'expires_at' => now()->addMinutes(5),
    ]);
    $token3->save();

    $pendingTokens = ScanLoginToken::whereState('state', 'pending')
        ->where('expires_at', '>', now())
        ->get();

    expect($pendingTokens)->toHaveCount(1);
    expect($pendingTokens->first()->token)->toBe('pending-valid');
});

it('can scope expired tokens', function () {
    // Create expired token with expired status
    $token1 = new ScanLoginToken();
    $token1->forceFill([
        'token' => 'expired-status',
        'state' => 'expired',
        'expires_at' => now()->addMinutes(5),
    ]);
    $token1->save();

    // Create token with past expiry date
    $token2 = new ScanLoginToken();
    $token2->forceFill([
        'token' => 'expired-time',
        'state' => 'pending',
        'expires_at' => now()->subMinutes(1),
    ]);
    $token2->save();

    // Create valid token
    $token3 = new ScanLoginToken();
    $token3->forceFill([
        'token' => 'valid-token',
        'state' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);
    $token3->save();

    $expiredTokens = ScanLoginToken::where(function ($query) {
        $query->whereState('state', 'expired')
              ->orWhere('expires_at', '<=', now());
    })->get();

    expect($expiredTokens)->toHaveCount(2);
    expect($expiredTokens->pluck('token'))->toContain('expired-status');
    expect($expiredTokens->pluck('token'))->toContain('expired-time');
});

it('casts dates properly', function () {
    $token = new ScanLoginToken();
    $token->forceFill([
        'token' => 'test-token',
        'state' => 'pending',
        'expires_at' => now()->addMinutes(5),
        'consumed_at' => now(),
    ]);
    $token->save();

    expect($token->expires_at)->toBeInstanceOf(Carbon::class);
    expect($token->consumed_at)->toBeInstanceOf(Carbon::class);
});

it('has correct fillable attributes', function () {
    $token = new ScanLoginToken();
    
    // Model uses fillable = [] which means no fields are fillable
    // This is intentional for this model as fields should be set through business logic
    expect($token->getFillable())->toBe([]);
});

it('uses correct table name', function () {
    $token = new ScanLoginToken();
    
    expect($token->getTable())->toBe('scan_login_tokens');
});

it('can create token with device information', function () {
    $token = new ScanLoginToken();
    $token->forceFill([
        'token' => 'test-token-with-device',
        'state' => 'pending',
        'expires_at' => now()->addMinutes(5),
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);
    $token->save();

    expect($token->ip_address)->toBe('192.168.1.100');
    expect($token->user_agent)->toBe('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
});

it('can get device info', function () {
    $token = new ScanLoginToken();
    $token->forceFill([
        'token' => 'device-info-token',
        'state' => 'pending',
        'expires_at' => now()->addMinutes(5),
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
    ]);
    $token->save();

    // Test that device info is stored correctly
    expect($token->ip_address)->toBe('192.168.1.100');
    expect($token->user_agent)->toBe('Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)');
});