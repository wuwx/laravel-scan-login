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