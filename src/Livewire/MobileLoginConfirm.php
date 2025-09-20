<?php

namespace Wuwx\LaravelScanLogin\Livewire;

use Livewire\Component;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MobileLoginConfirm extends Component
{
    public $token;
    public $user = null;
    public $deviceInfo = '';
    public $ipAddress = '获取中...';
    public $loginTime = '';
    public $status = 'loading';
    public $errorMessage = '';
    public $isSubmitting = false;

    public function mount($token)
    {
        $this->token = $token;
        
        if (!config('scan-login.enabled', true)) {
            abort(403, '扫码登录功能已禁用');
        }
        
        // Ensure user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        $this->user = Auth::user();
        $this->loginTime = now()->format('Y-m-d H:i');
        $this->deviceInfo = $this->getDeviceInfo();
        
        $this->loadTokenInfo();
        $this->loadIpAddress();
    }

    public function loadTokenInfo()
    {
        try {
            if (!config('scan-login.enabled', true)) {
                $this->setError('扫码登录功能已禁用');
                return;
            }

            $tokenManager = app(\Wuwx\LaravelScanLogin\Services\TokenManager::class);
            
            if ($tokenManager->validate($this->token)) {
                $this->status = 'ready';
            } else {
                $this->setError('无效的登录链接，请重新扫码');
            }
        } catch (\Exception $e) {
            Log::error('Failed to load token info in mobile confirm', [
                'error' => $e->getMessage(),
                'token' => $this->token,
            ]);
            
            $this->setError('获取登录信息失败');
        }
    }

    public function confirmLogin()
    {
        if ($this->isSubmitting) {
            return;
        }

        $this->isSubmitting = true;
        $this->errorMessage = '';

        try {
            $tokenManager = app(\Wuwx\LaravelScanLogin\Services\TokenManager::class);
            
            // Validate token first
            if (!$tokenManager->validate($this->token)) {
                $this->setError('登录令牌无效或已过期');
                return;
            }

            // Mark token as used
            $tokenManager->markAsUsed($this->token, $this->user->getAuthIdentifier());
            
            $this->status = 'success';
            
            // Auto-close after success
            $this->dispatch('loginSuccess');
        } catch (\Exception $e) {
            Log::error('Mobile login confirmation failed', [
                'error' => $e->getMessage(),
                'token' => $this->token,
                'user_id' => $this->user->id,
            ]);
            
            $this->setError('网络连接异常，请检查网络后重试');
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function cancelLogin()
    {
        if ($this->isSubmitting) {
            return;
        }

        $this->isSubmitting = true;

        try {
            $tokenManager = app(\Wuwx\LaravelScanLogin\Services\TokenManager::class);
            $tokenManager->cancel($this->token);
        } catch (\Exception $e) {
            Log::error('Mobile login cancellation failed', [
                'error' => $e->getMessage(),
                'token' => $this->token,
            ]);
        }

        // Always close the page after cancel, regardless of success
        $this->dispatch('loginCancelled');
    }

    private function loadIpAddress()
    {
        // This would typically be done server-side or via a separate API call
        // For now, we'll use a placeholder
        $this->ipAddress = request()->ip() ?? '无法获取';
    }

    private function getDeviceInfo()
    {
        $userAgent = request()->userAgent();
        
        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') || str_contains($userAgent, 'iPod')) {
            return 'iOS 设备';
        } elseif (str_contains($userAgent, 'Android')) {
            return 'Android 设备';
        } elseif (str_contains($userAgent, 'Windows')) {
            return 'Windows 设备';
        } elseif (str_contains($userAgent, 'Mac')) {
            return 'Mac 设备';
        } else {
            return '未知设备';
        }
    }

    private function setError($message)
    {
        $this->status = 'error';
        $this->errorMessage = $message;
    }

    public function render()
    {
        $layoutView = config('scan-login.mobile_layout_view', 'scan-login::layouts.mobile');
        
        return view('scan-login::livewire.mobile-login-confirm')
            ->layout($layoutView, [
                'title' => '扫码登录确认'
            ]);
    }
}