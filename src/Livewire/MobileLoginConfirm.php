<?php

namespace Wuwx\LaravelScanLogin\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Jenssegers\Agent\Agent;

class MobileLoginConfirm extends Component
{
    public $token;
    public $user = null;
    public $loginTime = '';
    public $status = 'loading';
    public $errorMessage = '';
    public $isSubmitting = false;
    public $ipAddress = '';
    private $agent = null;

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
            $this->agent = new Agent();
            $this->agent->setUserAgent($tokenRecord->user_agent ?? '');

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
    }



    private function setError($message)
    {
        $this->status = 'error';
        $this->errorMessage = $message;
    }

    public function render()
    {
        return view('scan-login::livewire.mobile-login-confirm', [
            'agent' => $this->agent,
        ]);
    }
}
