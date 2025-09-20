<?php

namespace Wuwx\LaravelScanLogin\Livewire\Pages;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Jenssegers\Agent\Agent;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed;

class MobileLoginConfirmPage extends Component
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

        $token = ScanLoginToken::where('token', $this->token)->first();
        app(ScanLoginTokenService::class)->markAsClaimed($token, $this->user->id);
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

            $service = app(\Wuwx\LaravelScanLogin\Services\ScanLoginTokenService::class);
            if ($service->validateToken($this->token)) {
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
            $service = app(ScanLoginTokenService::class);
            if (!$service->validateToken($this->token)) {
                $this->setError('登录令牌无效或已过期');
                return;
            }

            // Mark token as consumed
            $service = app(ScanLoginTokenService::class);
            $tokenRecord = ScanLoginToken::where('token', $this->token)
                ->whereIn('state', ['pending', 'claimed', 'consumed'])
                ->where('expires_at', '>', now())
                ->first();

            if ($tokenRecord) {
                $service->markAsConsumed($tokenRecord, $this->user->getAuthIdentifier());
            }

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

        $service = app(ScanLoginTokenService::class);
        $tokenRecord = ScanLoginToken::where('token', $this->token)->first();

        if ($tokenRecord) {
            $service->markAsCancelled($tokenRecord);
        }
    }



    private function setError($message)
    {
        $this->status = 'error';
        $this->errorMessage = $message;
    }

    public function render()
    {
        $token = ScanLoginToken::where('token', request()->route('token'))->first();
        return view('scan-login::livewire.pages.mobile-login-confirm-page', [
            'token' => $token,
            'agent' => $this->agent,
        ]);
    }
}
