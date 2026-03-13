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

    public function mount(ScanLoginTokenService $scanLoginTokenService, QrCodeService $qrCodeService): void
    {
        $this->token = $scanLoginTokenService->createToken();
        $this->qrCode = $this->buildQrCode($qrCodeService);
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

    public function refreshQrCode(ScanLoginTokenService $scanLoginTokenService, QrCodeService $qrCodeService): void
    {
        $this->token = $scanLoginTokenService->createToken();
        $this->qrCode = $this->buildQrCode($qrCodeService);
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
