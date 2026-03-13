<?php

namespace Wuwx\LaravelScanLogin\Livewire\Pages;

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

class QrCodeLoginPage extends Component
{
    #[Locked]
    public ScanLoginToken $token;

    public string $qrCode;

    /**
     * Polling interval in milliseconds passed to wire:poll in the view.
     *
     * When broadcasting is enabled this is set to the (longer) fallback interval
     * so that polling only serves as a safety net. When broadcasting is disabled
     * the normal interval from config('scan-login.polling_interval_seconds') is used.
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
     * re-hydration (and therefore a state check) without waiting for the next
     * polling tick.
     *
     * The listener format understood by Livewire 3's Echo bridge is:
     *   "echo:{channel},{BroadcastAs name}" => 'methodName'
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
     * broadcast is received on this token's channel.
     *
     * The method body is intentionally empty: merely receiving the call causes
     * Livewire to run a full server round-trip, which executes hydrate() and
     * handles the new state (redirect on consumed, refresh on cancelled, etc.).
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
        
        // Check if logo is enabled
        if (config('scan-login.qr_code.logo.enabled') && config('scan-login.qr_code.logo.path')) {
            $logoPath = config('scan-login.qr_code.logo.path');
            
            // Convert relative path to absolute
            if (!str_starts_with($logoPath, '/')) {
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
                'icon' => 'device-phone-mobile',
                'color' => 'text-sky-500',
                'background' => 'bg-sky-100 dark:bg-sky-900/30',
                'title' => '二维码已扫码',
                'description' => '请在手机上确认登录，二维码已隐藏以防止被再次扫描。',
            ];
        }

        if ($this->token->state->equals(ScanLoginTokenStateCancelled::class)) {
            return [
                'icon' => 'x-circle',
                'color' => 'text-zinc-500',
                'background' => 'bg-zinc-100 dark:bg-zinc-800/80',
                'title' => '二维码已取消',
                'description' => '这个登录请求已取消，请刷新页面重新生成二维码。',
            ];
        }

        if ($this->token->state->equals(ScanLoginTokenStateExpired::class)) {
            return [
                'icon' => 'clock',
                'color' => 'text-zinc-500',
                'background' => 'bg-zinc-100 dark:bg-zinc-800/80',
                'title' => '二维码已过期',
                'description' => '这个二维码已失效，请刷新页面重新生成二维码。',
            ];
        }

        if ($this->token->state->equals(ScanLoginTokenStateConsumed::class)) {
            return [
                'icon' => 'check-circle',
                'color' => 'text-green-500',
                'background' => 'bg-green-100 dark:bg-green-900/30',
                'title' => '登录已完成',
                'description' => '登录请求已经完成，正在为您跳转。',
            ];
        }

        return [
            'icon' => 'qr-code',
            'color' => 'text-zinc-500',
            'background' => 'bg-zinc-100 dark:bg-zinc-800/80',
            'title' => '二维码暂不可用',
            'description' => '请刷新页面重新生成二维码。',
        ];
    }

    public function render()
    {
        return view('scan-login::livewire.pages.qr-code-login-page');
    }
}
