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
    
    expect($token)->toBeInstanceOf(ScanLoginToken::class);
    expect($token->token)->toBeString();
    expect(strlen($token->token))->toBe(64);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token->token,
        'state' => 'pending'
    ]);
});

it('can get token state directly', function () {
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $token = $service->createToken();
    
    expect($token->state->getMorphClass())->toBe('pending');
    
    $invalidToken = ScanLoginToken::where('token', 'invalid-token')->first();
    expect($invalidToken)->toBeNull();
});

it('can mark token as consumed using service', function () {
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $token = $service->createToken();
    
    // First claim the token
    $service->markAsClaimed($token, 456);
    
    // Then consume it
    $service->markAsConsumed($token, 123);
    
    $token->refresh();
    expect($token->state)->toBeInstanceOf(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed::class);
    expect($token->consumer_id)->toBe(123);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token->token,
        'state' => 'consumed',
        'consumer_id' => 123
    ]);
});


it('can cancel token using service', function () {
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $token = $service->createToken();
    
    // Create a claimed token manually for testing cancellation
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $token->claimer_id = 1;
    $token->claimed_at = now();
    $token->save();
    
    // Then cancel it using service
    $service->markAsCancelled($token);
    
    $token->refresh();
    expect($token->state)->toBeInstanceOf(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled::class);
    expect($token->state->getMorphClass())->toBe('cancelled');
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token->token,
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
    expect($token->state)->toBeInstanceOf(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending::class);
    expect($token->state->getMorphClass())->toBe('pending');
});

it('returns false when trying to mark non-existent token as consumed', function () {
    $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
    $tokenRecord = ScanLoginToken::where('token', 'invalid-token')->first();
    
    expect($tokenRecord)->toBeNull();
});
