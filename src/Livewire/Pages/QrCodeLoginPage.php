<?php

namespace Wuwx\LaravelScanLogin\Livewire\Pages;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed;

class QrCodeLoginPage extends Component
{
    public $token = null;
    public $qrCode = null;
    public $status = 'loading';
    public $statusMessage = '正在生成二维码...';
    public $showRefreshButton = false;
    public $pollingInterval;
    public $tokenExpiryMinutes;

    public function mount()
    {
        if (!config('scan-login.enabled', true)) {
            abort(403, '扫码登录功能已禁用');
        }

        $this->pollingInterval = config('scan-login.polling_interval_seconds', 3) . 's';
        $this->tokenExpiryMinutes = config('scan-login.token_expiry_minutes', 5);

        $this->generateQrCode();
    }

    public function generateQrCode()
    {
        try {
            $this->resetState();

            if (!config('scan-login.enabled', true)) {
                $this->setError('扫码登录功能已禁用');
                return;
            }

            // Create token with device information
            $service = app(ScanLoginTokenService::class);
            $this->token = $service->createToken(request());

            // Generate QR code
            $this->qrCode = $this->createQrCode($this->token);

            $this->status = 'pending';
            $this->statusMessage = '等待扫码登录...';
        } catch (\Exception $e) {
            Log::error('QR code generation failed in Livewire component', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->setError('生成二维码失败，请稍后重试');
        }
    }

    public function checkLoginStatus()
    {
        $tokenRecord = $this->token->refresh();

        if ($tokenRecord->expires_at->isPast()) {
            $this->setError('二维码已过期，请刷新');
            return;
        }

        $status = $tokenRecord->state->getMorphClass();

        if ($tokenRecord->state->equals(ScanLoginTokenStateConsumed::class)) {
            $this->status = 'success';
            $this->statusMessage = '登录成功！正在跳转...';

            // Use Livewire's native redirect
            $redirectUrl = config('scan-login.login_success_redirect', '/');
            $this->redirect($redirectUrl);
        } elseif ($status === 'expired') {
            $this->setError('二维码已过期，请刷新');
        }
    }

    public function refreshQrCode()
    {
        $this->generateQrCode();
    }


    private function resetState()
    {
        $this->token = null;
        $this->qrCode = null;
        $this->status = 'loading';
        $this->statusMessage = '正在生成二维码...';
        $this->showRefreshButton = false;
    }

    private function setError($message)
    {
        $this->status = 'error';
        $this->statusMessage = $message;
        $this->showRefreshButton = true;
    }

    /**
     * Create QR code for the given token.
     */
    private function createQrCode(ScanLoginToken $token): string
    {
        $loginUrl = route("scan-login.mobile-login", $token->token);
        $size = config('scan-login.qr_code_size', 200);

        return QrCode::size($size)
            ->format('svg')
            ->generate($loginUrl);
    }

    public function render()
    {
        return view('scan-login::livewire.pages.qr-code-login-page');
    }
}
