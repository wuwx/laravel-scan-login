<?php

use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

it('can create token using static method', function () {
    $token = ScanLoginToken::createToken();
    
    expect($token)->toBeString();
    expect(strlen($token))->toBe(64);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'status' => 'pending'
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
    
    expect(ScanLoginToken::getTokenStatus($token))->toBe('pending');
    expect(ScanLoginToken::getTokenStatus('invalid-token'))->toBe('not_found');
});

it('can mark token as used using static method', function () {
    $token = ScanLoginToken::createToken();
    
    $result = ScanLoginToken::markTokenAsUsed($token, 123);
    
    expect($result)->toBeTrue();
    expect(ScanLoginToken::getTokenStatus($token))->toBe('used');
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'status' => 'used',
        'user_id' => 123
    ]);
});

it('can get token user id using static method', function () {
    $token = ScanLoginToken::createToken();
    ScanLoginToken::markTokenAsUsed($token, 456);
    
    expect(ScanLoginToken::getTokenUserId($token))->toBe(456);
    expect(ScanLoginToken::getTokenUserId('invalid-token'))->toBeNull();
});

it('can cancel token using static method', function () {
    $token = ScanLoginToken::createToken();
    
    $result = ScanLoginToken::cancelToken($token);
    
    expect($result)->toBeTrue();
    expect(ScanLoginToken::getTokenStatus($token))->toBe('cancelled');
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'status' => 'cancelled'
    ]);
});

it('returns false when trying to cancel non-existent token', function () {
    $result = ScanLoginToken::cancelToken('invalid-token');
    
    expect($result)->toBeFalse();
});

it('returns false when trying to mark non-existent token as used', function () {
    $result = ScanLoginToken::markTokenAsUsed('invalid-token', 123);
    
    expect($result)->toBeFalse();
});
