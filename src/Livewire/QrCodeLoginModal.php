<?php

namespace Wuwx\LaravelScanLogin\Livewire;

use Livewire\Component;

/**
 * Drop-in modal trigger for QR-code login.
 *
 * Renders a configurable trigger button. When clicked, an overlay appears
 * containing the QrCodeLogin widget. Closing the overlay destroys the widget
 * component so that re-opening it always starts fresh with a new token.
 *
 * Usage:
 *   <livewire:scan-login.qr-code-login-modal />
 *
 * Customise the trigger button label:
 *   <livewire:scan-login.qr-code-login-modal trigger-label="二维码扫码登录" />
 *
 * Publish the view to customise the button styling or overlay layout:
 *   php artisan vendor:publish --tag="scan-login-views"
 */
class QrCodeLoginModal extends Component
{
    public bool $open = false;

    public string $triggerLabel = '扫码登录';

    public function openModal(): void
    {
        $this->open = true;
    }

    public function closeModal(): void
    {
        $this->open = false;
    }

    public function render()
    {
        return view('scan-login::livewire.qr-code-login-modal');
    }
}
