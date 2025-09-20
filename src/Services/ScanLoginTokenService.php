<?php

namespace Wuwx\LaravelScanLogin\Services;

use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled;
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
        $token->update([
            'claimer_id' => $claimerId,
            'claimed_at' => now(),
        ]);
    }

    /**
     * Mark the token as consumed (login completed).
     */
    public function markAsConsumed(ScanLoginToken $token, int $consumerId): void
    {
        $token->state->transitionTo(ScanLoginTokenStateConsumed::class);
        $token->update([
            'consumer_id' => $consumerId,
            'consumed_at' => now(),
        ]);
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
        $token->update([
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Create a new login token.
     */
    public function createToken(Request $request = null, array $deviceInfo = null): string
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

        ScanLoginToken::create(array_merge([
            'token' => $token,
            'state' => 'pending',
            'expires_at' => $expiresAt,
        ], $deviceData));

        return $token;
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
