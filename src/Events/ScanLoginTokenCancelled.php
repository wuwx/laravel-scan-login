<?php

namespace Wuwx\LaravelScanLogin\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;

class ScanLoginTokenCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ScanLoginToken $token
    ) {}
}
