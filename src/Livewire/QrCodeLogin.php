<?php

namespace Wuwx\LaravelScanLogin\Livewire;

use Livewire\Component;

use Illuminate\Support\Facades\Log;

class QrCodeLogin extends Component
{
    public $token = null;
    public $qrCode = null;
    public $status = 'loading';
    public $statusMessage = '正在生成二维码...';
    public $showRefreshButton = false;
    public $showDiagnoseButton = false;
    public $config = [];
    public $isPolling = false;

    protected $listeners = ['refreshQrCode', 'checkLoginStatus'];

    public function mount()
    {
        $this->config = $this->getConfig();
        $this->generateQrCode();
    }

    public function generateQrCode()
    {
        try {
            $this->resetState();
            
            if (!$this->isEnabled()) {
                $this->setError('扫码登录功能已禁用');
                return;
            }

            // Create token directly
            $tokenManager = app(\Wuwx\LaravelScanLogin\Services\TokenManager::class);
            $this->token = $tokenManager->create();
            
            // Generate QR code directly
            $qrGenerator = app(\Wuwx\LaravelScanLogin\Services\QrCodeGenerator::class);
            $this->qrCode = $qrGenerator->generate($this->token);
            
            $this->status = 'pending';
            $this->statusMessage = '等待扫码登录...';
            $this->isPolling = true;
            
            // Start polling for status updates
            $this->dispatch('startPolling');
        } catch (\Exception $e) {
            Log::error('QR code generation failed in Livewire component', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->setError('生成二维码失败，请稍后重试');
            $this->showDiagnoseButton = true;
        }
    }

    public function checkLoginStatus()
    {
        if (!$this->token || !$this->isPolling) {
            return;
        }

        try {
            $tokenManager = app(\Wuwx\LaravelScanLogin\Services\TokenManager::class);
            $status = $tokenManager->getStatus($this->token);
            
            if ($status === 'used') {
                $this->status = 'success';
                $this->statusMessage = '登录成功！正在跳转...';
                $this->isPolling = false;
                
                // Redirect after a short delay
                $redirectUrl = config('scan-login.login_success_redirect', '/dashboard');
                $this->dispatch('redirectTo', url: $redirectUrl);
            } elseif ($status === 'expired') {
                $this->setError('二维码已过期，请刷新');
                $this->isPolling = false;
            }
        } catch (\Exception $e) {
            Log::error('Status check failed in Livewire component', [
                'error' => $e->getMessage(),
                'token' => $this->token,
            ]);
            
            // Don't show error for status check failures, just log them
            // The polling will continue and might recover
        }
    }

    public function refreshQrCode()
    {
        $this->generateQrCode();
    }

    public function diagnose()
    {
        try {
            // Get diagnostic information
            $diagnostics = [
                'enabled' => $this->isEnabled(),
                'config' => $this->getConfig(),
                'timestamp' => now()->toISOString(),
            ];
            
            Log::info('Scan login diagnostics requested', $diagnostics);
            
            $this->dispatch('showDiagnostics', diagnostics: $diagnostics);
        } catch (\Exception $e) {
            Log::error('Diagnostics failed', ['error' => $e->getMessage()]);
            $this->dispatch('showAlert', message: '诊断失败: ' . $e->getMessage());
        }
    }

    private function isEnabled(): bool
    {
        return (bool) config('scan-login.enabled', true);
    }

    private function getConfig(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'token_expiry_minutes' => (int) config('scan-login.token_expiry_minutes', 5),
            'polling_interval_seconds' => (int) config('scan-login.polling_interval_seconds', 3),
            'qr_code_size' => (int) config('scan-login.qr_code_size', 200),
            'login_success_redirect' => (string) config('scan-login.login_success_redirect', '/dashboard'),
        ];
    }

    private function resetState()
    {
        $this->token = null;
        $this->qrCode = null;
        $this->status = 'loading';
        $this->statusMessage = '正在生成二维码...';
        $this->showRefreshButton = false;
        $this->showDiagnoseButton = false;
        $this->isPolling = false;
    }

    private function setError($message)
    {
        $this->status = 'error';
        $this->statusMessage = $message;
        $this->showRefreshButton = true;
        $this->isPolling = false;
    }

    public function render()
    {
        return view('scan-login::livewire.qr-code-login');
    }
}