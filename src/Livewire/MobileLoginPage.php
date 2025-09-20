<?php

namespace Wuwx\LaravelScanLogin\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class MobileLoginPage extends Component
{
    public $token;

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
    }

    public function render()
    {
        $layoutView = config('scan-login.mobile_layout_view', 'scan-login::layouts.mobile');
        
        return view('scan-login::livewire.mobile-login-page')
            ->layout($layoutView, [
                'title' => '扫码登录确认'
            ]);
    }
}