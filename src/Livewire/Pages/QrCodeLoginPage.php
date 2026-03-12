<?php

namespace Wuwx\LaravelScanLogin\Livewire\Pages;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed;

class QrCodeLoginPage extends Component
{
    #[Locked]
    public ScanLoginToken $token;
    public string $qrCode;

    public function mount(ScanLoginTokenService $scanLoginTokenService)
    {
        $this->token = $scanLoginTokenService->createToken();
        $qrCodeSize = config('scan-login.qr_code_size', 200);

        $renderer = new ImageRenderer(
            new RendererStyle($qrCodeSize),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $this->qrCode = $writer->writeString(route("scan-login.mobile-login", $this->token->token));
    }

    public function hydrate()
    {
        $tokenRecord = $this->token->refresh();

        if ($tokenRecord->state->equals(ScanLoginTokenStateConsumed::class)) {
            Auth::loginUsingId($this->token->consumer_id);
            $redirectUrl = config('scan-login.login_success_redirect', '/');
            $this->redirect($redirectUrl);
        }
    }

    public function render()
    {
        return view('scan-login::livewire.pages.qr-code-login-page');
    }
}
