<?php

use Illuminate\Support\Facades\Event;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenCancelled;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenClaimed;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenConsumed;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenCreated;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenExpired;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;

beforeEach(function () {
    Event::fake();
});

it('dispatches token created event', function () {
    $service = app(ScanLoginTokenService::class);
    
    $token = $service->createToken();

    Event::assertDispatched(ScanLoginTokenCreated::class, function ($event) use ($token) {
        return $event->token->id === $token->id;
    });
});

it('dispatches token claimed event', function () {
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();
    $claimerId = 1;

    $service->markAsClaimed($token, $claimerId);

    Event::assertDispatched(ScanLoginTokenClaimed::class, function ($event) use ($token, $claimerId) {
        return $event->token->id === $token->id 
            && $event->claimerId === $claimerId;
    });
});

it('dispatches token consumed event', function () {
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();
    $claimerId = 1;
    $consumerId = 1;

    $service->markAsClaimed($token, $claimerId);
    $service->markAsConsumed($token, $consumerId);

    Event::assertDispatched(ScanLoginTokenConsumed::class, function ($event) use ($token, $consumerId) {
        return $event->token->id === $token->id 
            && $event->consumerId === $consumerId;
    });
});

it('dispatches token cancelled event', function () {
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();
    $claimerId = 1;

    $service->markAsClaimed($token, $claimerId);
    $service->markAsCancelled($token);

    Event::assertDispatched(ScanLoginTokenCancelled::class, function ($event) use ($token) {
        return $event->token->id === $token->id;
    });
});

it('dispatches token expired event', function () {
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();

    $service->markAsExpired($token);

    Event::assertDispatched(ScanLoginTokenExpired::class, function ($event) use ($token) {
        return $event->token->id === $token->id;
    });
});

it('does not dispatch event when state transition fails', function () {
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();
    $consumerId = 1;

    // 尝试直接标记为 consumed（跳过 claimed 状态）
    $result = $service->markAsConsumed($token, $consumerId);

    expect($result)->toBeFalse();
    Event::assertNotDispatched(ScanLoginTokenConsumed::class);
});

it('can listen to multiple events', function () {
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();
    $claimerId = 1;
    $consumerId = 1;

    // 完整的登录流程
    $service->markAsClaimed($token, $claimerId);
    $service->markAsConsumed($token, $consumerId);

    // 验证所有事件都被触发
    Event::assertDispatched(ScanLoginTokenCreated::class);
    Event::assertDispatched(ScanLoginTokenClaimed::class);
    Event::assertDispatched(ScanLoginTokenConsumed::class);
});

it('event contains correct token data', function () {
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();

    Event::assertDispatched(ScanLoginTokenCreated::class, function ($event) use ($token) {
        return $event->token->token === $token->token
            && $event->token->ip_address === $token->ip_address
            && $event->token->user_agent === $token->user_agent;
    });
});
