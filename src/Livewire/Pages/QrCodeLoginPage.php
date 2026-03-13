<?php

namespace Wuwx\LaravelScanLogin\Livewire\Pages;

use Livewire\Component;

/**
 * Full-page QR-code login component.
 *
 * This is a thin layout wrapper registered on the `scan-login/` route.
 * All token management, state machine, polling and broadcasting logic
 * lives in the embedded QrCodeLogin widget component.
 *
 * To use the same functionality inside a modal instead of a dedicated
 * page, embed the modal component anywhere in your views:
 *
 *   <livewire:scan-login.qr-code-login-modal />
 */
class QrCodeLoginPage extends Component
{
    public function render()
    {
        return view('scan-login::livewire.pages.qr-code-login-page');
    }
}
