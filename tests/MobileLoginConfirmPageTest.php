<?php

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Wuwx\LaravelScanLogin\Livewire\Pages\MobileLoginConfirmPage;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed;

uses(RefreshDatabase::class);

it('blocks scanning a token that has already been consumed', function () {
    $token = new ScanLoginToken();
    $token->forceFill([
        'token' => 'consumed-token',
        'state' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);
    $token->save();

    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $token->claimer_id = 99;
    $token->claimed_at = now();
    $token->save();

    $token->state->transitionTo(ScanLoginTokenStateConsumed::class);
    $token->consumer_id = 99;
    $token->consumed_at = now();
    $token->save();

    $user = new class extends Authenticatable
    {
        protected $guarded = [];
    };
    $user->id = 1;

    $this->actingAs($user);

    Livewire::test(MobileLoginConfirmPage::class, ['token' => $token])
        ->assertSee('二维码已被使用')
        ->assertDontSee('确认登录');

    $token->refresh();

    expect($token->state)->toBeInstanceOf(ScanLoginTokenStateConsumed::class);
    expect($token->consumer_id)->toBe(99);
});

it('blocks a different user from accessing a token claimed by another user', function () {
    $token = new ScanLoginToken();
    $token->forceFill([
        'token' => 'claimed-by-other-token',
        'state' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);
    $token->save();

    // Claim token as user 99
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $token->claimer_id = 99;
    $token->claimed_at = now();
    $token->save();

    // Attempt to access as user 1 (different user)
    $user = new class extends Authenticatable
    {
        protected $guarded = [];
    };
    $user->id = 1;

    $this->actingAs($user);

    Livewire::test(MobileLoginConfirmPage::class, ['token' => $token])
        ->assertSet('result', 'token-claimed');
});

it('shows expired result when token is expired before mounting', function () {
    $token = new ScanLoginToken();
    $token->forceFill([
        'token' => 'expired-before-mount-token',
        'state' => 'expired',
        'expires_at' => now()->subMinutes(5),
    ]);
    $token->save();

    $user = new class extends Authenticatable
    {
        protected $guarded = [];
    };
    $user->id = 1;

    $this->actingAs($user);

    Livewire::test(MobileLoginConfirmPage::class, ['token' => $token])
        ->assertSet('result', 'token-expired');
});

it('shows cancelled result when token is cancelled before mounting', function () {
    $token = new ScanLoginToken();
    $token->forceFill([
        'token' => 'cancelled-before-mount-token',
        'state' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);
    $token->save();

    // Claim and then cancel the token
    $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
    $token->claimer_id = 99;
    $token->claimed_at = now();
    $token->save();

    $token->state->transitionTo(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled::class);
    $token->cancelled_at = now();
    $token->save();

    $user = new class extends Authenticatable
    {
        protected $guarded = [];
    };
    $user->id = 1;

    $this->actingAs($user);

    Livewire::test(MobileLoginConfirmPage::class, ['token' => $token])
        ->assertSet('result', 'token-cancelled');
});