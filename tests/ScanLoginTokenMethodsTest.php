<?php

use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

it('can create token using static method', function () {
    $token = ScanLoginToken::createToken();
    
    expect($token)->toBeString();
    expect(strlen($token))->toBe(64);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending'
    ]);
});

it('can create token with request', function () {
    $request = Request::create('/test', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Test Browser',
        'REMOTE_ADDR' => '192.168.1.1'
    ]);
    
    $token = ScanLoginToken::createToken($request);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Test Browser'
    ]);
});

it('can validate token using static method', function () {
    $token = ScanLoginToken::createToken();
    
    expect(ScanLoginToken::validateToken($token))->toBeTrue();
    expect(ScanLoginToken::validateToken('invalid-token'))->toBeFalse();
});

it('can get token status using static method', function () {
    $token = ScanLoginToken::createToken();
    
    expect(ScanLoginToken::getTokenStatus($token))->toBe('Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending');
    expect(ScanLoginToken::getTokenStatus('invalid-token'))->toBe('not_found');
});

it('can mark token as used using static method', function () {
    $token = ScanLoginToken::createToken();
    
    $result = ScanLoginToken::markTokenAsUsed($token, 123);
    
    expect($result)->toBeTrue();
    expect(ScanLoginToken::getTokenStatus($token))->toBe('Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed');
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed',
        'consumer_id' => 123
    ]);
});


it('can cancel token using static method', function () {
    $token = ScanLoginToken::createToken();
    
    // First claim the token (scan it)
    ScanLoginToken::markTokenAsClaimed($token, 1);
    
    // Then cancel it
    $result = ScanLoginToken::cancelToken($token);
    
    expect($result)->toBeTrue();
    expect(ScanLoginToken::getTokenStatus($token))->toBe('Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled');
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled'
    ]);
});

it('returns false when trying to cancel non-existent token', function () {
    $result = ScanLoginToken::cancelToken('invalid-token');
    
    expect($result)->toBeFalse();
});

it('returns false when trying to cancel pending token', function () {
    $token = ScanLoginToken::createToken();
    
    // Try to cancel a pending token (should fail)
    $result = ScanLoginToken::cancelToken($token);
    
    expect($result)->toBeFalse();
    expect(ScanLoginToken::getTokenStatus($token))->toBe('Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending');
});

it('returns false when trying to mark non-existent token as used', function () {
    $result = ScanLoginToken::markTokenAsUsed('invalid-token', 123);
    
    expect($result)->toBeFalse();
});
