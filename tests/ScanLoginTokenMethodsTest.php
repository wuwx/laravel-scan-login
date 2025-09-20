<?php

use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

it('can create token using service', function () {
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $token = $service->createToken();
    
    expect($token)->toBeString();
    expect(strlen($token))->toBe(64);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'state' => 'pending'
    ]);
});

it('can create token with request', function () {
    $request = Request::create('/test', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Test Browser',
        'REMOTE_ADDR' => '192.168.1.1'
    ]);
    
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $token = $service->createToken($request);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Test Browser'
    ]);
});

it('can validate token using service', function () {
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $token = $service->createToken();
    
    expect($service->validateToken($token))->toBeTrue();
    expect($service->validateToken('invalid-token'))->toBeFalse();
});

it('can get token state directly', function () {
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $token = $service->createToken();
    
    $tokenRecord = ScanLoginToken::where('token', $token)->first();
    expect($tokenRecord->state->getMorphClass())->toBe('pending');
    
    $invalidToken = ScanLoginToken::where('token', 'invalid-token')->first();
    expect($invalidToken)->toBeNull();
});

it('can mark token as consumed using service', function () {
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $token = $service->createToken();
    
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $tokenRecord = ScanLoginToken::where('token', $token)->first();
    
    $service->markAsConsumed($tokenRecord, 123);
    
    $tokenRecord->refresh();
    expect($tokenRecord->state)->toBeInstanceOf(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed::class);
    expect($tokenRecord->consumer_id)->toBe(123);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'state' => 'consumed',
        'consumer_id' => 123
    ]);
});


it('can cancel token using service', function () {
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $token = $service->createToken();
    
    // Create a claimed token manually for testing cancellation
    $tokenRecord = ScanLoginToken::where('token', $token)->first();
    $tokenRecord->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $tokenRecord->claimer_id = 1;
    $tokenRecord->claimed_at = now();
    $tokenRecord->save();
    
    // Then cancel it using service
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $service->markAsCancelled($tokenRecord);
    
    $tokenRecord->refresh();
    expect($tokenRecord->state)->toBeInstanceOf(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled::class);
    expect($tokenRecord->state->getMorphClass())->toBe('cancelled');
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'state' => 'cancelled'
    ]);
});

it('returns null when trying to cancel non-existent token', function () {
    $tokenRecord = ScanLoginToken::where('token', 'invalid-token')->first();
    
    expect($tokenRecord)->toBeNull();
});

it('cannot cancel pending token', function () {
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $token = $service->createToken();
    
    // Try to cancel a pending token (should fail because pending tokens can't be cancelled)
    $tokenRecord = ScanLoginToken::where('token', $token)->first();
    expect($tokenRecord->state)->toBeInstanceOf(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending::class);
    expect($tokenRecord->state->getMorphClass())->toBe('pending');
});

it('returns false when trying to mark non-existent token as consumed', function () {
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $tokenRecord = ScanLoginToken::where('token', 'invalid-token')->first();
    
    expect($tokenRecord)->toBeNull();
});
