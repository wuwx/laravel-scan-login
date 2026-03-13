<?php

namespace Wuwx\LaravelScanLogin\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\QrCodeService;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending;

/**
 * Embeddable QR-code login widget.
 *
 * Contains all token management, state machine, polling and broadcasting
 * logic. Can be embedded anywhere with:
 *
 *   <livewire:scan-login.qr-code-login />
 *
 * Used by both QrCodeLoginPage (full-page) and QrCodeLoginModal (overlay).
 */
class QrCodeLogin extends Component
{
    #[Locked]
    public ScanLoginToken $token;

    public string $qrCode;

    /**
     * Polling interval in milliseconds.
     *
     * When broadcasting is enabled this is set to the (longer) fallback
     * interval so that polling only serves as a safety net.
     */
    public int $pollingIntervalMs;

    public function mount(ScanLoginTokenService $scanLoginTokenService, QrCodeService $qrCodeService): void
    {
        $this->token = $scanLoginTokenService->createToken();
        $this->qrCode = $this->buildQrCode($qrCodeService);
        $this->pollingIntervalMs = $this->resolvePollingIntervalMs();
    }

    public function hydrate(ScanLoginTokenService $scanLoginTokenService, QrCodeService $qrCodeService): void
    {
        $tokenRecord = $this->token->refresh();

        if ($tokenRecord->expires_at->isPast()) {
            $scanLoginTokenService->markAsExpired($tokenRecord);
            $tokenRecord = $this->token->refresh();
        }

        if ($tokenRecord->state->equals(ScanLoginTokenStateConsumed::class)) {
            Auth::loginUsingId($this->token->consumer_id);
            $redirectUrl = config('scan-login.login_success_redirect', '/');
            $this->redirect($redirectUrl);

            return;
        }

        // Auto-refresh when cancelled by mobile so the user can scan again immediately
        if ($tokenRecord->state->equals(ScanLoginTokenStateCancelled::class)) {
            $this->token = $scanLoginTokenService->createToken();
            $this->qrCode = $this->buildQrCode($qrCodeService);
        }
    }

    /**
     * Dynamic Livewire event listeners.
     *
     * When broadcasting is enabled, subscribes to the token-specific public
     * Echo channel so that any WebSocket push triggers an immediate component
     * re-hydration without waiting for the next polling tick.
     */
    protected function getListeners(): array
    {
        if (! config('scan-login.broadcasting.enabled', false)) {
            return [];
        }

        $prefix = config('scan-login.broadcasting.channel_prefix', 'scan-login');

        return [
            "echo:{$prefix}.{$this->token->token},ScanLoginTokenStateUpdated" => 'handleBroadcastUpdate',
        ];
    }

    /**
     * Called by the Livewire Echo bridge when a ScanLoginTokenStateUpdated
     * broadcast is received. The empty body is intentional — the Livewire
     * round-trip itself runs hydrate() which handles the new state.
     */
    public function handleBroadcastUpdate(): void {}

    public function refreshQrCode(ScanLoginTokenService $scanLoginTokenService, QrCodeService $qrCodeService): void
    {
        $this->token = $scanLoginTokenService->createToken();
        $this->qrCode = $this->buildQrCode($qrCodeService);
    }

    private function resolvePollingIntervalMs(): int
    {
        if (config('scan-login.broadcasting.enabled', false)) {
            return (int) (config('scan-login.broadcasting.fallback_polling_seconds', 15) * 1000);
        }

        return (int) (config('scan-login.polling_interval_seconds', 3) * 1000);
    }

    private function buildQrCode(QrCodeService $qrCodeService): string
    {
        $url = route('scan-login.mobile-login', $this->token->token);

        if (config('scan-login.qr_code.logo.enabled') && config('scan-login.qr_code.logo.path')) {
            $logoPath = config('scan-login.qr_code.logo.path');

            if (! str_starts_with($logoPath, '/')) {
                $logoPath = public_path($logoPath);
            }

            if (file_exists($logoPath)) {
                try {
                    return $qrCodeService->generateWithLogo($url, $logoPath);
                } catch (\Exception $e) {
                    Log::warning('Failed to generate QR code with logo: ' . $e->getMessage());

                    return $qrCodeService->generate($url);
                }
            }
        }

        return $qrCodeService->generate($url);
    }

    public function shouldDisplayQrCode(): bool
    {
        return $this->token->state->equals(ScanLoginTokenStatePending::class);
    }

    public function qrPlaceholder(): array
    {
        if ($this->token->state->equals(ScanLoginTokenStateClaimed::class)) {
            return [
                // Heroicons v2 outline: device-phone-mobile
                'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3" />',
                'color' => 'text-sky-500',
                'background' => 'bg-sky-100 dark:bg-sky-900/30',
                'title' => '二维码已扫码',
                'description' => '请在手机上确认登录，二维码已隐藏以防止被再次扫描。',
            ];
        }

        if ($this->token->state->equals(ScanLoginTokenStateCancelled::class)) {
            return [
                // Heroicons v2 outline: x-circle
                'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />',
                'color' => 'text-zinc-500',
                'background' => 'bg-zinc-100 dark:bg-zinc-800/80',
                'title' => '二维码已取消',
                'description' => '这个登录请求已取消，请刷新页面重新生成二维码。',
            ];
        }

        if ($this->token->state->equals(ScanLoginTokenStateExpired::class)) {
            return [
                // Heroicons v2 outline: clock
                'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />',
                'color' => 'text-zinc-500',
                'background' => 'bg-zinc-100 dark:bg-zinc-800/80',
                'title' => '二维码已过期',
                'description' => '这个二维码已失效，请刷新页面重新生成二维码。',
            ];
        }

        if ($this->token->state->equals(ScanLoginTokenStateConsumed::class)) {
            return [
                // Heroicons v2 outline: check-circle
                'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />',
                'color' => 'text-green-500',
                'background' => 'bg-green-100 dark:bg-green-900/30',
                'title' => '登录已完成',
                'description' => '登录请求已经完成，正在为您跳转。',
            ];
        }

        return [
            // Heroicons v2 outline: qr-code
            'icon_path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5ZM6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />',
            'color' => 'text-zinc-500',
            'background' => 'bg-zinc-100 dark:bg-zinc-800/80',
            'title' => '二维码暂不可用',
            'description' => '请刷新页面重新生成二维码。',
        ];
    }

    public function render()
    {
        return view('scan-login::livewire.qr-code-login');
    }
}
