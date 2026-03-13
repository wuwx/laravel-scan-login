<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenCancelled;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenClaimed;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenConsumed;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenCreated;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenExpired;
use Wuwx\LaravelScanLogin\Listeners\LogScanLoginActivity;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;

beforeEach(function () {
    config(['scan-login.enable_default_listeners' => true]);
});

it('logs token created event', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Scan login token created', \Mockery::type('array'));
    
    $token = ScanLoginToken::factory()->create();
    
    $listener = new LogScanLoginActivity();
    $event = new ScanLoginTokenCreated($token);
    $listener->handleTokenCreated($event);
});

it('logs token claimed event', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Scan login token claimed', \Mockery::type('array'));
    
    $token = ScanLoginToken::factory()->create();
    $claimerId = 1;
    
    $listener = new LogScanLoginActivity();
    $event = new ScanLoginTokenClaimed($token, $claimerId);
    $listener->handleTokenClaimed($event);
});

it('logs token consumed event', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Scan login token consumed (login successful)', \Mockery::type('array'));
    
    $token = ScanLoginToken::factory()->create();
    $consumerId = 1;
    
    $listener = new LogScanLoginActivity();
    $event = new ScanLoginTokenConsumed($token, $consumerId);
    $listener->handleTokenConsumed($event);
});

it('logs token cancelled event', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Scan login token cancelled', \Mockery::type('array'));
    
    $token = ScanLoginToken::factory()->create();
    
    $listener = new LogScanLoginActivity();
    $event = new ScanLoginTokenCancelled($token);
    $listener->handleTokenCancelled($event);
});

it('logs token expired event', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Scan login token expired', \Mockery::type('array'));
    
    $token = ScanLoginToken::factory()->create();
    
    $listener = new LogScanLoginActivity();
    $event = new ScanLoginTokenExpired($token);
    $listener->handleTokenExpired($event);
});

it('listener has correct event subscriptions', function () {
    $listener = new LogScanLoginActivity();
    $subscriptions = $listener->subscribe(null);
    
    expect($subscriptions)->toBeArray()
        ->toHaveKey(ScanLoginTokenCreated::class)
        ->toHaveKey(ScanLoginTokenClaimed::class)
        ->toHaveKey(ScanLoginTokenConsumed::class)
        ->toHaveKey(ScanLoginTokenCancelled::class)
        ->toHaveKey(ScanLoginTokenExpired::class);
});

it('logs include token id and ip address', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Scan login token created', \Mockery::on(function ($context) {
            return isset($context['token_id']) 
                && isset($context['ip_address'])
                && isset($context['expires_at']);
        }));
    
    $token = ScanLoginToken::factory()->create();
    
    $listener = new LogScanLoginActivity();
    $event = new ScanLoginTokenCreated($token);
    $listener->handleTokenCreated($event);
});

it('logs include claimer id for claimed event', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Scan login token claimed', \Mockery::on(function ($context) {
            return isset($context['token_id']) 
                && isset($context['claimer_id'])
                && isset($context['ip_address']);
        }));
    
    $token = ScanLoginToken::factory()->create();
    $claimerId = 123;
    
    $listener = new LogScanLoginActivity();
    $event = new ScanLoginTokenClaimed($token, $claimerId);
    $listener->handleTokenClaimed($event);
});

it('logs include consumer id for consumed event', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Scan login token consumed (login successful)', \Mockery::on(function ($context) {
            return isset($context['token_id']) 
                && isset($context['consumer_id'])
                && isset($context['ip_address']);
        }));
    
    $token = ScanLoginToken::factory()->create();
    $consumerId = 456;
    
    $listener = new LogScanLoginActivity();
    $event = new ScanLoginTokenConsumed($token, $consumerId);
    $listener->handleTokenConsumed($event);
});
