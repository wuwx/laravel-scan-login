<?php

namespace Wuwx\LaravelScanLogin\Livewire\Pages;

use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed;

class MobileLoginConfirmPage extends Component
{
    #[Locked]
    public ScanLoginToken $token;

    public function mount(ScanLoginTokenService $scanLoginTokenService)
    {
        if ($this->token->state->canTransitionTo(ScanLoginTokenStateClaimed::class)) {
            $scanLoginTokenService->markAsClaimed($this->token, Auth::id());
        }
    }

    public function consume(ScanLoginTokenService $scanLoginTokenService)
    {

        $scanLoginTokenService->markAsConsumed($this->token, Auth::id());
    }

    public function cancel(ScanLoginTokenService $scanLoginTokenService)
    {
        $scanLoginTokenService->markAsCancelled($this->token);
    }

    public function render()
    {
        $agent = new Agent();
        $agent->setUserAgent($this->token->user_agent);
        return view('scan-login::livewire.pages.mobile-login-confirm-page', [
            'agent' => $agent,
        ]);
    }
}
