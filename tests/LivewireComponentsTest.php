<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Wuwx\LaravelScanLogin\Livewire\QrCodeLogin;
use Wuwx\LaravelScanLogin\Livewire\QrCodeLoginModal;
use Wuwx\LaravelScanLogin\Livewire\Pages\MobileLoginConfirmPage;
use Wuwx\LaravelScanLogin\Livewire\Pages\QrCodeLoginPage;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;
use Wuwx\LaravelScanLogin\Tests\Fixtures\User;

// ---------------------------------------------------------------------------
// QrCodeLogin widget
// ---------------------------------------------------------------------------

it('qr code widget generates token and qr code', function () {
    Livewire::test(QrCodeLogin::class)
        ->assertSet('token', fn ($token) => $token instanceof ScanLoginToken)
        ->assertSet('qrCode', fn ($qrCode) => ! empty($qrCode));
});

it('qr code widget can refresh qr code', function () {
    $component = Livewire::test(QrCodeLogin::class);

    $originalToken = $component->get('token');

    $component->call('refreshQrCode');

    $newToken = $component->get('token');

    expect($newToken->id)->not->toBe($originalToken->id);
});

it('qr code widget displays correct placeholder for different states', function () {
    $service = app(ScanLoginTokenService::class);

    $component = Livewire::test(QrCodeLogin::class);

    // Pending state — shouldDisplayQrCode returns true
    expect($component->instance()->shouldDisplayQrCode())->toBeTrue();

    // Claimed state
    $claimedToken = $component->instance()->token;
    $service->markAsClaimed($claimedToken, 1);
    $component->instance()->token = $claimedToken;
    expect($component->instance()->qrPlaceholder()['title'])->toContain('已扫码');

    // Expired state
    $expiredToken = $service->createToken();
    $service->markAsExpired($expiredToken);
    $component->instance()->token = $expiredToken;
    expect($component->instance()->qrPlaceholder()['title'])->toContain('已过期');
});

it('qr code page renders without error', function () {
    Livewire::test(QrCodeLoginPage::class)
        ->assertStatus(200);
});

// ---------------------------------------------------------------------------
// QrCodeLoginModal
// ---------------------------------------------------------------------------

it('qr code modal is closed by default', function () {
    Livewire::test(QrCodeLoginModal::class)
        ->assertSet('open', false);
});

it('qr code modal opens on openModal call', function () {
    Livewire::test(QrCodeLoginModal::class)
        ->call('openModal')
        ->assertSet('open', true);
});

it('qr code modal closes on closeModal call', function () {
    Livewire::test(QrCodeLoginModal::class)
        ->call('openModal')
        ->call('closeModal')
        ->assertSet('open', false);
});

it('qr code modal accepts custom trigger label', function () {
    Livewire::test(QrCodeLoginModal::class, ['triggerLabel' => '扫码登录系统'])
        ->assertSet('triggerLabel', '扫码登录系统')
        ->assertSee('扫码登录系统');
});

// ---------------------------------------------------------------------------
// MobileLoginConfirmPage (unchanged)
// ---------------------------------------------------------------------------


it('mobile confirm page marks token as claimed', function () {
    $user = User::factory()->create();
    Auth::login($user);
    
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();
    
    Livewire::test(MobileLoginConfirmPage::class, ['token' => $token])
        ->assertSet('result', null);
    
    $token->refresh();
    expect($token->state)->toBeInstanceOf(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed::class);
});

it('mobile confirm page can consume token', function () {
    $user = User::factory()->create();
    Auth::login($user);
    
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();
    
    // Let mount() handle claiming automatically, then consume
    Livewire::test(MobileLoginConfirmPage::class, ['token' => $token])
        ->call('consume')
        ->assertSet('result', 'login-approved');
    
    $token->refresh();
    expect($token->state)->toBeInstanceOf(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed::class);
});

it('mobile confirm page can cancel token', function () {
    $user = User::factory()->create();
    Auth::login($user);
    
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();
    
    // Let mount() handle claiming automatically, then cancel
    Livewire::test(MobileLoginConfirmPage::class, ['token' => $token])
        ->call('cancel')
        ->assertSet('result', 'login-cancelled');
    
    $token->refresh();
    expect($token->state)->toBeInstanceOf(\Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled::class);
});

it('mobile confirm page shows expired message for expired token', function () {
    $user = User::factory()->create();
    Auth::login($user);
    
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();
    
    // Manually expire the token
    $token->expires_at = now()->subMinutes(10);
    $token->save();
    
    Livewire::test(MobileLoginConfirmPage::class, ['token' => $token])
        ->assertSet('result', 'token-expired');
});

it('mobile confirm page consume is idempotent', function () {
    $user = User::factory()->create();
    Auth::login($user);
    
    $service = app(ScanLoginTokenService::class);
    $token = $service->createToken();
    
    // Let mount() claim and then consume
    $component = Livewire::test(MobileLoginConfirmPage::class, ['token' => $token])
        ->call('consume')
        ->assertSet('result', 'login-approved');
    
    // Calling consume again after result is already set should be a no-op
    $component->call('consume')->assertSet('result', 'login-approved');
});
