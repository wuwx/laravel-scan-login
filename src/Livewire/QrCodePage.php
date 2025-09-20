<?php

namespace Wuwx\LaravelScanLogin\Livewire;

use Livewire\Component;
class QrCodePage extends Component
{
    public function mount()
    {
        if (!config('scan-login.enabled', true)) {
            abort(403, '扫码登录功能已禁用');
        }
    }

    public function render()
    {
        $layoutView = config('scan-login.layout_view', 'scan-login::layouts.app');
        
        return view('scan-login::livewire.qr-code-page')
            ->layout($layoutView, [
                'title' => '扫码登录'
            ]);
    }
}