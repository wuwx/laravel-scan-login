<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Wuwx\LaravelScanLogin\Livewire\Pages\MobileLoginConfirmPage;
use Wuwx\LaravelScanLogin\Livewire\Pages\QrCodeLoginPage;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;
use Wuwx\LaravelScanLogin\Tests\Fixtures\User;

it('qr code page generates token and qr code', function () {
    Livewire::test(QrCodeLoginPage::class)
        ->assertSet('token', fn($token) => $token instanceof ScanLoginToken)
        ->assertSet('qrCode', fn($qrCode) => !empty($qrCode));
});

it('qr code page can refresh qr code', function () {
    $component = Livewire::test(QrCodeLoginPage::class);
    
    $originalToken = $component->get('token');
    
    $component->call('refreshQrCode');
    
    $newToken = $component->get('token');
    
    expect($newToken->id)->not->toBe($originalToken->id);
});

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

it('qr code page displays correct placeholder for different states', function () {
    $service = app(ScanLoginTokenService::class);
    
    $component = Livewire::test(QrCodeLoginPage::class);
    
    // Pending state - shouldDisplayQrCode returns true
    expect($component->instance()->shouldDisplayQrCode())->toBeTrue();
    
    // Claimed state - directly assign to the component instance (bypasses #[Locked])
    $claimedToken = $component->instance()->token;
    $service->markAsClaimed($claimedToken, 1);
    $component->instance()->token = $claimedToken;
    $placeholder = $component->instance()->qrPlaceholder();
    expect($placeholder['title'])->toContain('已扫码');
    
    // Expired state
    $expiredToken = $service->createToken();
    $service->markAsExpired($expiredToken);
    $component->instance()->token = $expiredToken;
    $placeholder = $component->instance()->qrPlaceholder();
    expect($placeholder['title'])->toContain('已过期');
});
