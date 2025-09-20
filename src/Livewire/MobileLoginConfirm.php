<?php

namespace Wuwx\LaravelScanLogin\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;

class MobileLoginConfirm extends Component
{
    public $token;
    public $user = null;
    public $loginTime = '';
    public $status = 'loading';
    public $errorMessage = '';
    public $isSubmitting = false;
    public $deviceInfo = '';
    public $ipAddress = '';

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
        $this->loadTokenInfo();
    }

    public function loadTokenInfo()
    {
        try {
            if (!config('scan-login.enabled', true)) {
                $this->setError('扫码登录功能已禁用');
                return;
            }

            // Get token record to extract device information
            $tokenRecord = ScanLoginToken::where('token', $this->token)->first();
            
            if (!$tokenRecord) {
                $this->setError('无效的登录链接，请重新扫码');
                return;
            }

            // Extract device information from token record
            $this->ipAddress = $tokenRecord->ip_address ?? '未知';
            $this->deviceInfo = $this->parseUserAgent($tokenRecord->user_agent ?? '');

            if (ScanLoginToken::validateToken($this->token)) {
                $this->status = 'ready';
            } else {
                $this->setError('登录令牌已过期，请重新扫码');
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
            // Validate token first
            if (!ScanLoginToken::validateToken($this->token)) {
                $this->setError('登录令牌无效或已过期');
                return;
            }

            // Mark token as used
            ScanLoginToken::markTokenAsUsed($this->token, $this->user->getAuthIdentifier());

            $this->status = 'success';

            // Use session flash message for success feedback
            session()->flash('scan_login_success', '登录成功！桌面端将自动跳转。');

            // Auto-close the page using JavaScript (since we can't redirect to close)
            $this->dispatch('close-window');
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
            ScanLoginToken::cancelToken($this->token);
        } catch (\Exception $e) {
            Log::error('Mobile login cancellation failed', [
                'error' => $e->getMessage(),
                'token' => $this->token,
            ]);
        }

        // Close the page after cancel
        $this->dispatch('close-window');
    }


    private function parseUserAgent(string $userAgent): string
    {
        if (empty($userAgent)) {
            return '未知设备';
        }

        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') || str_contains($userAgent, 'iPod')) {
            return 'iOS 设备';
        } elseif (str_contains($userAgent, 'Android')) {
            return 'Android 设备';
        } elseif (str_contains($userAgent, 'Windows')) {
            return 'Windows 设备';
        } elseif (str_contains($userAgent, 'Mac')) {
            return 'Mac 设备';
        } elseif (str_contains($userAgent, 'Linux')) {
            return 'Linux 设备';
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
        return view('scan-login::livewire.mobile-login-confirm');
    }
}
