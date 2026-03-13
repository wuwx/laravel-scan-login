<?php

namespace Wuwx\LaravelScanLogin\Listeners;

use Illuminate\Support\Facades\Log;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenCancelled;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenClaimed;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenConsumed;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenCreated;
use Wuwx\LaravelScanLogin\Events\ScanLoginTokenExpired;

class LogScanLoginActivity
{
    /**
     * Handle token created event.
     */
    public function handleTokenCreated(ScanLoginTokenCreated $event): void
    {
        Log::info('Scan login token created', [
            'token_id' => $event->token->id,
            'ip_address' => $event->token->ip_address,
            'expires_at' => $event->token->expires_at,
        ]);
    }

    /**
     * Handle token claimed event.
     */
    public function handleTokenClaimed(ScanLoginTokenClaimed $event): void
    {
        Log::info('Scan login token claimed', [
            'token_id' => $event->token->id,
            'claimer_id' => $event->claimerId,
            'ip_address' => $event->token->ip_address,
        ]);
    }

    /**
     * Handle token consumed event.
     */
    public function handleTokenConsumed(ScanLoginTokenConsumed $event): void
    {
        Log::info('Scan login token consumed (login successful)', [
            'token_id' => $event->token->id,
            'consumer_id' => $event->consumerId,
            'ip_address' => $event->token->ip_address,
        ]);
    }

    /**
     * Handle token cancelled event.
     */
    public function handleTokenCancelled(ScanLoginTokenCancelled $event): void
    {
        Log::info('Scan login token cancelled', [
            'token_id' => $event->token->id,
            'ip_address' => $event->token->ip_address,
        ]);
    }

    /**
     * Handle token expired event.
     */
    public function handleTokenExpired(ScanLoginTokenExpired $event): void
    {
        Log::info('Scan login token expired', [
            'token_id' => $event->token->id,
            'ip_address' => $event->token->ip_address,
            'expired_at' => $event->token->expires_at,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            ScanLoginTokenCreated::class => 'handleTokenCreated',
            ScanLoginTokenClaimed::class => 'handleTokenClaimed',
            ScanLoginTokenConsumed::class => 'handleTokenConsumed',
            ScanLoginTokenCancelled::class => 'handleTokenCancelled',
            ScanLoginTokenExpired::class => 'handleTokenExpired',
        ];
    }
}
