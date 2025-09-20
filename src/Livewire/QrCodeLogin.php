<?php

namespace Wuwx\LaravelScanLogin\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class QrCodeLogin extends Component
{
    public $token = null;
    public $qrCode = null;
    public $status = 'loading';
    public $statusMessage = '正在生成二维码...';
    public $showRefreshButton = false;
    public $showDiagnoseButton = false;
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

            // Create token directly
            $tokenManager = app(\Wuwx\LaravelScanLogin\Services\TokenManager::class);
            $this->token = $tokenManager->create();
            
            // Generate QR code directly
            $qrGenerator = app(\Wuwx\LaravelScanLogin\Services\QrCodeGenerator::class);
            $this->qrCode = $qrGenerator->generate($this->token);
            
            $this->status = 'pending';
            $this->statusMessage = '等待扫码登录...';
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
        if (!$this->token || $this->status !== 'pending') {
            return;
        }

        try {
            $tokenManager = app(\Wuwx\LaravelScanLogin\Services\TokenManager::class);
            $status = $tokenManager->getStatus($this->token);
            
            if ($status === 'used') {
                $this->status = 'success';
                $this->statusMessage = '登录成功！正在跳转...';
                
                // Use Livewire's native redirect
                $redirectUrl = config('scan-login.login_success_redirect', '/dashboard');
                $this->redirect($redirectUrl);
            } elseif ($status === 'expired') {
                $this->setError('二维码已过期，请刷新');
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
                'enabled' => config('scan-login.enabled', true),
                'config' => [
                    'token_expiry_minutes' => $this->tokenExpiryMinutes,
                    'polling_interval' => $this->pollingInterval,
                    'qr_code_size' => config('scan-login.qr_code_size', 200),
                ],
                'timestamp' => now()->toISOString(),
            ];
            
            Log::info('Scan login diagnostics requested', $diagnostics);
            
            // Use session flash for diagnostics instead of JavaScript
            session()->flash('scan_login_diagnostics', $diagnostics);
            $this->dispatch('diagnostics-ready');
        } catch (\Exception $e) {
            Log::error('Diagnostics failed', ['error' => $e->getMessage()]);
            session()->flash('scan_login_error', '诊断失败: ' . $e->getMessage());
        }
    }

    private function resetState()
    {
        $this->token = null;
        $this->qrCode = null;
        $this->status = 'loading';
        $this->statusMessage = '正在生成二维码...';
        $this->showRefreshButton = false;
        $this->showDiagnoseButton = false;
    }

    private function setError($message)
    {
        $this->status = 'error';
        $this->statusMessage = $message;
        $this->showRefreshButton = true;
    }

    public function render()
    {
        return view('scan-login::livewire.qr-code-login');
    }
}