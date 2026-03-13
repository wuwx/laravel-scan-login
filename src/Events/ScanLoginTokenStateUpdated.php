<?php

namespace Wuwx\LaravelScanLogin\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;

/**
 * Broadcast event fired whenever a ScanLoginToken transitions to a new state.
 *
 * Used by the desktop QR-code Livewire component to receive real-time updates
 * via Pusher, Laravel Reverb, or any compatible WebSocket driver.
 *
 * Broadcasting is only active when `scan-login.broadcasting.enabled = true`.
 * When disabled, broadcastOn() returns an empty array and nothing is sent.
 */
class ScanLoginTokenStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ScanLoginToken $token) {}

    /**
     * Returns the public channel for this token.
     *
     * A public (not private) channel is used because the desktop user is
     * unauthenticated at this point. The 64-character random token value
     * serves as the capability secret — only the holder of the QR code URL
     * can construct or subscribe to this channel name.
     *
     * Returns an empty array when broadcasting is disabled, which prevents
     * Laravel from queuing or sending any broadcast message.
     */
    public function broadcastOn(): Channel|array
    {
        if (! config('scan-login.broadcasting.enabled', false)) {
            return [];
        }

        $prefix = config('scan-login.broadcasting.channel_prefix', 'scan-login');

        return new Channel("{$prefix}.{$this->token->token}");
    }

    public function broadcastAs(): string
    {
        return 'ScanLoginTokenStateUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'state' => class_basename($this->token->state),
        ];
    }
}
