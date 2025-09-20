<?php

namespace Wuwx\LaravelScanLogin\Services;

use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScanLoginTokenService
{
    /**
     * Mark the token as claimed (QR code was scanned).
     */
    public function markAsClaimed(ScanLoginToken $token, int $claimerId): void
    {
        $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
        $token->claimer_id = $claimerId;
        $token->claimed_at = now();
        $token->save();
    }

    /**
     * Mark the token as consumed (login completed).
     */
    public function markAsConsumed(ScanLoginToken $token, int $consumerId): void
    {
        // First claim the token if it's still pending
        if ($token->state->canTransitionTo(ScanLoginTokenStateClaimed::class)) {
            $token->state->transitionTo(ScanLoginTokenStateClaimed::class);
            $token->claimer_id = $consumerId;
            $token->claimed_at = now();
            $token->save();
        }

        // Then consume it
        $token->state->transitionTo(ScanLoginTokenStateConsumed::class);
        $token->consumer_id = $consumerId;
        $token->consumed_at = now();
        $token->save();
    }

    /**
     * Mark the token as expired.
     */
    public function markAsExpired(ScanLoginToken $token): void
    {
        $token->state->transitionTo(ScanLoginTokenStateExpired::class);
    }

    /**
     * Mark the token as cancelled.
     */
    public function markAsCancelled(ScanLoginToken $token): void
    {
        $token->state->transitionTo(ScanLoginTokenStateCancelled::class);
        $token->cancelled_at = now();
        $token->save();
    }

    /**
     * Create a new login token.
     */
    public function createToken(Request $request = null, array $deviceInfo = null): ScanLoginToken
    {
        $token = Str::random(64);
        $expiryMinutes = config('scan-login.token_expiry_minutes', 5);
        $expiresAt = now()->addMinutes($expiryMinutes);

        // Prepare device information
        $deviceData = [];
        if ($deviceInfo) {
            $deviceData = [
                'ip_address' => $deviceInfo['ip_address'] ?? null,
                'user_agent' => $deviceInfo['user_agent'] ?? null,
            ];
        } elseif ($request) {
            $deviceData = [
                'ip_address' => $request->ip() ?? '0.0.0.0',
                'user_agent' => $request->userAgent(),
            ];
        }

        $scanLoginToken = new ScanLoginToken();
        $scanLoginToken->forceFill(array_merge([
            'token' => $token,
            'state' => 'pending',
            'expires_at' => $expiresAt,
        ], $deviceData));
        $scanLoginToken->save();

        return $scanLoginToken;
    }

    /**
     * Validate if a token exists and is valid.
     */
    public function validateToken(string $token): bool
    {
        $tokenRecord = ScanLoginToken::where('token', $token)->first();

        if (!$tokenRecord) {
            return false;
        }

        return in_array($tokenRecord->state->getMorphClass(), [
                'pending',
                'claimed',
                'consumed'
            ]) && $tokenRecord->expires_at->isFuture();
    }




}
