<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenClaimed;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenConsumed;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenCreated;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;
use Wuwx\LaravelScanLogin\Tests\Fixtures\User;

it('completes full login flow', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $service = app(ScanLoginTokenService::class);
    
    // Step 1: Create token (desktop)
    $token = $service->createToken();
    
    expect($token)->toBeInstanceOf(ScanLoginToken::class)
        ->and($token->token)->not->toBeEmpty()
        ->and($token->expires_at)->toBeGreaterThan(now());
    
    Event::assertDispatched(ScanLoginTokenCreated::class);
    
    // Step 2: Claim token (mobile scan)
    $claimed = $service->markAsClaimed($token, $user->id);
    
    expect($claimed)->toBeTrue()
        ->and($token->refresh()->claimer_id)->toBe($user->id);
    
    Event::assertDispatched(ScanLoginTokenClaimed::class);
    
    // Step 3: Consume token (mobile confirm)
    $consumed = $service->markAsConsumed($token, $user->id);
    
    expect($consumed)->toBeTrue()
        ->and($token->refresh()->consumer_id)->toBe($user->id);
    
    Event::assertDispatched(ScanLoginTokenConsumed::class);
});

it('prevents invalid state transitions', function () {
    $user = User::factory()->create();
    $service = app(ScanLoginTokenService::class);
    
    $token = $service->createToken();
    
    // Try to consume without claiming first
    $result = $service->markAsConsumed($token, $user->id);
    
    expect($result)->toBeFalse();
});

it('handles expired tokens correctly', function () {
    $service = app(ScanLoginTokenService::class);
    
    $token = $service->createToken();
    
    // Manually expire the token
    $token->expires_at = now()->subMinutes(10);
    $token->save();
    
    // Try to claim expired token
    $result = $service->markAsClaimed($token, 1);
    
    expect($result)->toBeFalse();
});

it('prevents token reuse', function () {
    $user = User::factory()->create();
    $service = app(ScanLoginTokenService::class);
    
    $token = $service->createToken();
    $service->markAsClaimed($token, $user->id);
    $service->markAsConsumed($token, $user->id);
    
    // Try to claim again
    $result = $service->markAsClaimed($token, $user->id);
    
    expect($result)->toBeFalse();
});

it('allows cancellation and prevents further actions', function () {
    $user = User::factory()->create();
    $service = app(ScanLoginTokenService::class);
    
    $token = $service->createToken();
    $service->markAsClaimed($token, $user->id);
    
    // Cancel the token
    $cancelled = $service->markAsCancelled($token);
    
    expect($cancelled)->toBeTrue();
    
    // Try to consume cancelled token
    $result = $service->markAsConsumed($token, $user->id);
    
    expect($result)->toBeFalse();
});

it('records device information', function () {
    $service = app(ScanLoginTokenService::class);
    
    $token = $service->createToken();
    
    expect($token->ip_address)->not->toBeNull()
        ->and($token->user_agent)->not->toBeNull();
});

it('enforces token expiry time from config', function () {
    config(['scan-login.token_expiry_minutes' => 10]);
    
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();
    
    $expectedExpiry = now()->addMinutes(10);
    
    expect($token->expires_at->timestamp)
        ->toBeGreaterThanOrEqual($expectedExpiry->timestamp - 1)
        ->toBeLessThanOrEqual($expectedExpiry->timestamp + 1);
});

it('generates unique tokens', function () {
    $service = app(ScanLoginTokenService::class);
    
    $token1 = $service->createToken();
    $token2 = $service->createToken();
    
    expect($token1->token)->not->toBe($token2->token);
});

it('respects token length configuration', function () {
    config(['scan-login.token_length' => 32]);
    
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();
    
    expect(strlen($token->token))->toBe(32);
});

it('cannot expire a consumed token', function () {
    $user = User::factory()->create();
    $service = app(ScanLoginTokenService::class);

    $token = $service->createToken();
    $service->markAsClaimed($token, $user->id);
    $service->markAsConsumed($token, $user->id);

    $result = $service->markAsExpired($token);

    expect($result)->toBeFalse();
});

it('cannot consume an expired token', function () {
    $user = User::factory()->create();
    $service = app(ScanLoginTokenService::class);

    $token = $service->createToken();
    $service->markAsExpired($token);

    $result = $service->markAsConsumed($token, $user->id);

    expect($result)->toBeFalse();
});

it('cannot claim an expired token', function () {
    $user = User::factory()->create();
    $service = app(ScanLoginTokenService::class);

    $token = $service->createToken();

    // Manually expire by time (service will now refuse the claim)
    $token->expires_at = now()->subMinutes(10);
    $token->save();

    $result = $service->markAsClaimed($token, $user->id);

    expect($result)->toBeFalse();
});

it('markAsCancelled dispatches event', function () {
    Event::fake();

    $user = User::factory()->create();
    $service = app(ScanLoginTokenService::class);

    $token = $service->createToken();
    $service->markAsClaimed($token, $user->id);
    $service->markAsCancelled($token);

    Event::assertDispatched(\Wuwx\LaravelScanLogin\Events\ScanLoginTokenCancelled::class);
});
