<?php

use Wuwx\LaravelScanLogin\Services\TokenManager;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

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
    
    $this->tokenManager = new TokenManager();
});

it('can create a new token', function () {
    $token = $this->tokenManager->create();

    expect($token)->toBeString();
    expect(strlen($token))->toBe(64);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'status' => 'pending',
    ]);
});

it('creates token with correct expiry time', function () {
    config(['scan-login.token_expiry_minutes' => 10]);
    
    $token = $this->tokenManager->create();
    
    $tokenRecord = ScanLoginToken::where('token', $token)->first();
    $expectedExpiry = now()->addMinutes(10);
    
    expect($tokenRecord->expires_at->diffInSeconds($expectedExpiry))->toBeLessThan(2);
});

it('validates existing pending token', function () {
    $token = $this->tokenManager->create();
    
    expect($this->tokenManager->validate($token))->toBeTrue();
});

it('rejects non existent token', function () {
    expect($this->tokenManager->validate('non-existent-token'))->toBeFalse();
});

it('rejects expired token', function () {
    $token = $this->tokenManager->create();
    
    // Manually expire the token
    $tokenRecord = ScanLoginToken::where('token', $token)->first();
    $tokenRecord->update(['expires_at' => now()->subMinutes(1)]);
    
    expect($this->tokenManager->validate($token))->toBeFalse();
});

it('rejects used token', function () {
    $token = $this->tokenManager->create();
    $this->tokenManager->markAsUsed($token, 1);
    
    expect($this->tokenManager->validate($token))->toBeFalse();
});

it('can mark token as used', function () {
    $token = $this->tokenManager->create();
    $userId = 123;
    
    $this->tokenManager->markAsUsed($token, $userId);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'status' => 'used',
        'user_id' => $userId,
    ]);
    
    $tokenRecord = ScanLoginToken::where('token', $token)->first();
    expect($tokenRecord->used_at)->not->toBeNull();
});

it('does not mark expired token as used', function () {
    $token = $this->tokenManager->create();
    
    // Expire the token
    $tokenRecord = ScanLoginToken::where('token', $token)->first();
    $tokenRecord->update(['expires_at' => now()->subMinutes(1)]);
    
    $this->tokenManager->markAsUsed($token, 123);
    
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'status' => 'pending', // Should remain pending
        'user_id' => null,
    ]);
});

it('returns correct status for pending token', function () {
    $token = $this->tokenManager->create();
    
    expect($this->tokenManager->getStatus($token))->toBe('pending');
});

it('returns correct status for used token', function () {
    $token = $this->tokenManager->create();
    $this->tokenManager->markAsUsed($token, 123);
    
    expect($this->tokenManager->getStatus($token))->toBe('used');
});

it('returns expired status and updates token', function () {
    $token = $this->tokenManager->create();
    
    // Manually expire the token
    $tokenRecord = ScanLoginToken::where('token', $token)->first();
    $tokenRecord->update(['expires_at' => now()->subMinutes(1)]);
    
    expect($this->tokenManager->getStatus($token))->toBe('expired');
    
    // Verify the token was marked as expired in database
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'status' => 'expired',
    ]);
});

it('returns not found for non existent token', function () {
    expect($this->tokenManager->getStatus('non-existent'))->toBe('not_found');
});

it('returns user id for used token', function () {
    $token = $this->tokenManager->create();
    $userId = 456;
    
    $this->tokenManager->markAsUsed($token, $userId);
    
    expect($this->tokenManager->getUserId($token))->toBe($userId);
});

it('returns null for unused token', function () {
    $token = $this->tokenManager->create();
    
    expect($this->tokenManager->getUserId($token))->toBeNull();
});

it('can cleanup expired tokens', function () {
    // Create some tokens
    $validToken = $this->tokenManager->create();
    $expiredToken1 = $this->tokenManager->create();
    $expiredToken2 = $this->tokenManager->create();
    
    // Expire some tokens
    ScanLoginToken::whereIn('token', [$expiredToken1, $expiredToken2])
        ->update(['expires_at' => now()->subMinutes(1)]);
    
    $deletedCount = $this->tokenManager->cleanup();
    
    expect($deletedCount)->toBe(2);
    $this->assertDatabaseHas('scan_login_tokens', ['token' => $validToken]);
    $this->assertDatabaseMissing('scan_login_tokens', ['token' => $expiredToken1]);
    $this->assertDatabaseMissing('scan_login_tokens', ['token' => $expiredToken2]);
});

it('can get token record', function () {
    $token = $this->tokenManager->create();
    
    $tokenRecord = $this->tokenManager->getTokenRecord($token);
    
    expect($tokenRecord)->toBeInstanceOf(ScanLoginToken::class);
    expect($tokenRecord->token)->toBe($token);
});

it('returns null for non existent token record', function () {
    $tokenRecord = $this->tokenManager->getTokenRecord('non-existent');
    
    expect($tokenRecord)->toBeNull();
});