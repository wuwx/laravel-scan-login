<?php

use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending;

it('has pending as default state', function () {
    $token = ScanLoginToken::factory()->create();
    
    expect($token->state)->toBeInstanceOf(ScanLoginTokenStatePending::class);
});

it('can transition from pending to claimed', function () {
    $token = ScanLoginToken::factory()->create();
    
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    
    expect($token->state)->toBeInstanceOf(ScanLoginTokenStateClaimed::class);
});

it('can transition from pending to expired', function () {
    $token = ScanLoginToken::factory()->create();
    
    $token->state->transitionTo(ScanLoginTokenStateExpired::class);
    
    expect($token->state)->toBeInstanceOf(ScanLoginTokenStateExpired::class);
});

it('can transition from claimed to consumed', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    
    $token->state->transitionTo(ScanLoginTokenStateConsumed::class);
    
    expect($token->state)->toBeInstanceOf(ScanLoginTokenStateConsumed::class);
});

it('can transition from claimed to cancelled', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    
    $token->state->transitionTo(ScanLoginTokenStateCancelled::class);
    
    expect($token->state)->toBeInstanceOf(ScanLoginTokenStateCancelled::class);
});

it('can transition from claimed to expired', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    
    $token->state->transitionTo(ScanLoginTokenStateExpired::class);
    
    expect($token->state)->toBeInstanceOf(ScanLoginTokenStateExpired::class);
});

it('cannot transition from pending to consumed', function () {
    $token = ScanLoginToken::factory()->create();
    
    expect(fn() => $token->state->transitionTo(ScanLoginTokenStateConsumed::class))
        ->toThrow(\Spatie\ModelStates\Exceptions\TransitionNotFound::class);
});

it('cannot transition from consumed to any state', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $token->state->transitionTo(ScanLoginTokenStateConsumed::class);
    
    expect(fn() => $token->state->transitionTo(ScanLoginTokenStatePending::class))
        ->toThrow(\Spatie\ModelStates\Exceptions\TransitionNotFound::class);
});

it('pending state has correct color', function () {
    $token = ScanLoginToken::factory()->create();
    
    expect($token->state->getColor())->toBe('blue');
});

it('claimed state has correct color', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    
    expect($token->state->getColor())->toBe('yellow');
});

it('consumed state has correct color', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $token->state->transitionTo(ScanLoginTokenStateConsumed::class);
    
    expect($token->state->getColor())->toBe('green');
});

it('cancelled state has correct color', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $token->state->transitionTo(ScanLoginTokenStateCancelled::class);
    
    expect($token->state->getColor())->toBe('red');
});

it('expired state has correct color', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateExpired::class);
    
    expect($token->state->getColor())->toBe('gray');
});

it('pending state has description', function () {
    $token = ScanLoginToken::factory()->create();
    
    expect($token->state->getDescription())->toBeString()->not->toBeEmpty();
});

it('claimed state has description', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    
    expect($token->state->getDescription())->toBeString()->not->toBeEmpty();
});

it('consumed state has description', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $token->state->transitionTo(ScanLoginTokenStateConsumed::class);
    
    expect($token->state->getDescription())->toBeString()->not->toBeEmpty();
});

it('cancelled state has description', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $token->state->transitionTo(ScanLoginTokenStateCancelled::class);
    
    expect($token->state->getDescription())->toBeString()->not->toBeEmpty();
});

it('expired state has description', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateExpired::class);
    
    expect($token->state->getDescription())->toBeString()->not->toBeEmpty();
});

it('state names are correct', function () {
    expect(ScanLoginTokenStatePending::$name)->toBe('pending')
        ->and(ScanLoginTokenStateClaimed::$name)->toBe('claimed')
        ->and(ScanLoginTokenStateConsumed::$name)->toBe('consumed')
        ->and(ScanLoginTokenStateCancelled::$name)->toBe('cancelled')
        ->and(ScanLoginTokenStateExpired::$name)->toBe('expired');
});

it('persists state transitions to database', function () {
    $token = ScanLoginToken::factory()->create();
    $tokenId = $token->id;
    
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $token->save();
    
    // 重新从数据库加载
    $reloadedToken = ScanLoginToken::find($tokenId);
    
    expect($reloadedToken->state)->toBeInstanceOf(ScanLoginTokenStateClaimed::class);
});

it('cannot transition from pending to cancelled', function () {
    $token = ScanLoginToken::factory()->create();

    expect(fn() => $token->state->transitionTo(ScanLoginTokenStateCancelled::class))
        ->toThrow(\Spatie\ModelStates\Exceptions\TransitionNotFound::class);
});

it('cannot transition from expired state', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateExpired::class);

    expect(fn() => $token->state->transitionTo(ScanLoginTokenStatePending::class))
        ->toThrow(\Spatie\ModelStates\Exceptions\TransitionNotFound::class);
});

it('cannot transition from cancelled state', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $token->state->transitionTo(ScanLoginTokenStateCancelled::class);

    expect(fn() => $token->state->transitionTo(ScanLoginTokenStatePending::class))
        ->toThrow(\Spatie\ModelStates\Exceptions\TransitionNotFound::class);
});

it('cannot transition from consumed to cancelled', function () {
    $token = ScanLoginToken::factory()->create();
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $token->state->transitionTo(ScanLoginTokenStateConsumed::class);

    expect(fn() => $token->state->transitionTo(ScanLoginTokenStateCancelled::class))
        ->toThrow(\Spatie\ModelStates\Exceptions\TransitionNotFound::class);
});

it('state can check transition availability', function () {
    $token = ScanLoginToken::factory()->create();

    expect($token->state->canTransitionTo(ScanLoginTokenStateClaimed::class))->toBeTrue();
    expect($token->state->canTransitionTo(ScanLoginTokenStateExpired::class))->toBeTrue();
    expect($token->state->canTransitionTo(ScanLoginTokenStateCancelled::class))->toBeFalse();
    expect($token->state->canTransitionTo(ScanLoginTokenStateConsumed::class))->toBeFalse();
});
