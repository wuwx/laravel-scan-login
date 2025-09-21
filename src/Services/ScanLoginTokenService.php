<?php

namespace Wuwx\LaravelScanLogin\Services;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired;

class ScanLoginTokenService
{
    /**
     * Mark the token as claimed (QR code was scanned).
     */
    public function markAsClaimed(ScanLoginToken $token, int $claimerId): bool
    {
        if (!$token->state->canTransitionTo(ScanLoginTokenStateClaimed::class)) {
            return false;
        }

        $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
        $token->claimer_id = $claimerId;
        $token->claimed_at = now();
        $token->save();
        return true;
    }

    /**
     * Mark the token as consumed (login completed).
     */
    public function markAsConsumed(ScanLoginToken $token, int $consumerId): bool
    {
        if (!$token->state->canTransitionTo(ScanLoginTokenStateConsumed::class)) {
            return false;
        }

        $token->state->transitionTo(ScanLoginTokenStateConsumed::class);
        $token->consumer_id = $consumerId;
        $token->consumed_at = now();
        $token->save();
        return true;
    }

    /**
     * Mark the token as expired.
     */
    public function markAsExpired(ScanLoginToken $token): bool
    {
        if (!$token->state->canTransitionTo(ScanLoginTokenStateExpired::class)) {
            return false;
        }

        $token->state->transitionTo(ScanLoginTokenStateExpired::class);
        return true;
    }

    /**
     * Mark the token as cancelled.
     */
    public function markAsCancelled(ScanLoginToken $token): bool
    {
        if (!$token->state->canTransitionTo(ScanLoginTokenStateCancelled::class)) {
            return false;
        }

        $token->state->transitionTo(ScanLoginTokenStateCancelled::class);
        $token->cancelled_at = now();
        $token->save();
        return true;
    }

    /**
     * Create a new login token.
     */
    public function createToken(): ScanLoginToken
    {
        $scanLoginToken = new ScanLoginToken();
        $scanLoginToken->token = Str::random(config('scan-login.token_length', 64));
        $scanLoginToken->expires_at = now()->addMinutes(config('scan-login.token_expiry_minutes', 5));
        $scanLoginToken->user_agent = Request::userAgent();
        $scanLoginToken->ip_address = Request::getClientIp();
        $scanLoginToken->save();
        return $scanLoginToken;
    }


}
