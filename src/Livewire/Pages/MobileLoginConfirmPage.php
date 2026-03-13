<?php

namespace Wuwx\LaravelScanLogin\Livewire\Pages;

use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\GeoLocationService;
use Wuwx\LaravelScanLogin\Services\RateLimitService;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired;

class MobileLoginConfirmPage extends Component
{
    #[Locked]
    public ScanLoginToken $token;

    public ?string $result = null;

    public function mount(ScanLoginTokenService $scanLoginTokenService, RateLimitService $rateLimitService): void
    {
        $this->token->refresh();

        if ($this->token->expires_at->isPast()) {
            $scanLoginTokenService->markAsExpired($this->token);
            $this->token->refresh();
        }

        $blockedResult = $this->resolveBlockedResult();

        if ($blockedResult !== null) {
            $this->result = $blockedResult;

            return;
        }

        // 检查速率限制
        if ($rateLimitService->shouldLimit(request(), 'token_claim')) {
            $this->result = 'rate-limit-exceeded';

            return;
        }

        if (! $scanLoginTokenService->markAsClaimed($this->token, (int) Auth::id())) {
            $this->token->refresh();
            $this->result = $this->resolveBlockedResult() ?? 'token-unavailable';
        }
    }

    public function consume(ScanLoginTokenService $scanLoginTokenService, RateLimitService $rateLimitService): void
    {
        if ($this->result !== null) {
            return;
        }

        // 检查速率限制
        if ($rateLimitService->shouldLimit(request(), 'token_consume')) {
            $this->result = 'rate-limit-exceeded';

            return;
        }

        if ($scanLoginTokenService->markAsConsumed($this->token, (int) Auth::id())) {
            $this->result = 'login-approved';

            return;
        }

        $this->token->refresh();
        $this->result = $this->resolveBlockedResult() ?? 'token-unavailable';
    }

    public function cancel(ScanLoginTokenService $scanLoginTokenService): void
    {
        if ($this->result !== null) {
            return;
        }

        if ($scanLoginTokenService->markAsCancelled($this->token)) {
            $this->result = 'login-cancelled';

            return;
        }

        $this->token->refresh();
        $this->result = $this->resolveBlockedResult() ?? 'token-unavailable';
    }

    protected function resolveBlockedResult(): ?string
    {
        if ($this->token->state instanceof ScanLoginTokenStateConsumed) {
            return 'token-consumed';
        }

        if ($this->token->state instanceof ScanLoginTokenStateCancelled) {
            return 'token-cancelled';
        }

        if ($this->token->state instanceof ScanLoginTokenStateExpired) {
            return 'token-expired';
        }

        if (
            $this->token->state instanceof ScanLoginTokenStateClaimed
            && (int) $this->token->claimer_id !== (int) Auth::id()
        ) {
            return 'token-claimed';
        }

        return null;
    }

    public function render(GeoLocationService $geoLocationService)
    {
        $agent = new Agent();
        $agent->setUserAgent($this->token->user_agent);

        // 获取详细 User Agent 信息
        $platform = $agent->platform();
        $platformVersion = $platform ? $agent->version($platform) : null;
        $browser = $agent->browser();
        $browserVersion = $browser ? $agent->version($browser) : null;
        $device = $agent->device();

        // 获取地理位置（使用 GeoLocationService）
        $location = $geoLocationService->getLocationFromIp($this->token->ip_address);

        return view('scan-login::livewire.pages.mobile-login-confirm-page', [
            'agent' => $agent,
            'platform' => $platform,
            'platformVersion' => $platformVersion,
            'browser' => $browser,
            'browserVersion' => $browserVersion,
            'device' => $device,
            'location' => $location,
            'ip' => $this->token->ip_address,
        ]);
    }
}
