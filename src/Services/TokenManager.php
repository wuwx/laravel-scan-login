<?php

namespace Wuwx\LaravelScanLogin\Services;

use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TokenManager
{
    public function __construct()
    {
        // No cache configuration needed
    }

    /**
     * Create a new login token.
     */
    public function create(): string
    {
        $token = $this->generateSecureToken();
        $expiryMinutes = config('scan-login.token_expiry_minutes', 5);
        $expiresAt = now()->addMinutes($expiryMinutes);
        
        ScanLoginToken::create([
            'token' => $token,
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    /**
     * Validate if a token exists and is valid.
     */
    public function validate(string $token): bool
    {
        $tokenRecord = DB::table('scan_login_tokens')
            ->where('token', $token)
            ->first();
        
        if (!$tokenRecord) {
            return false;
        }

        return $tokenRecord->status === 'pending' && Carbon::parse($tokenRecord->expires_at)->isFuture();
    }

    /**
     * Mark a token as used by a specific user.
     */
    public function markAsUsed(string $token, int $userId): void
    {
        DB::table('scan_login_tokens')
            ->where('token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->update([
                'status' => 'used',
                'user_id' => $userId,
                'used_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Get the status of a token.
     */
    public function getStatus(string $token): string
    {
        $tokenRecord = DB::table('scan_login_tokens')
            ->where('token', $token)
            ->first();
        
        if (!$tokenRecord) {
            return 'not_found';
        }

        if (Carbon::parse($tokenRecord->expires_at)->isPast()) {
            // Mark as expired if it's past expiry time but still pending
            if ($tokenRecord->status === 'pending') {
                $this->markAsExpired($token);
            }
            return 'expired';
        }

        return $tokenRecord->status;
    }

    /**
     * Get the user ID associated with a used token.
     */
    public function getUserId(string $token): ?int
    {
        $tokenRecord = DB::table('scan_login_tokens')
            ->where('token', $token)
            ->where('status', 'used')
            ->first();
        
        return $tokenRecord ? $tokenRecord->user_id : null;
    }

    /**
     * Clean up expired tokens.
     */
    public function cleanup(): int
    {
        return $this->cleanupExpiredTokens();
    }

    /**
     * Clean up expired tokens in batches.
     */
    public function cleanupExpiredTokens(int $batchSize = null): int
    {
        $batchSize = $batchSize ?? config('scan-login.cleanup_batch_size', 1000);
        
        // Delete expired tokens from database
        return DB::table('scan_login_tokens')
            ->where(function ($query) {
                $query->where('status', 'expired')
                      ->orWhere('expires_at', '<=', now());
            })
            ->limit($batchSize)
            ->delete();
    }

    /**
     * Get count of expired tokens.
     */
    public function getExpiredTokensCount(): int
    {
        return DB::table('scan_login_tokens')
            ->where(function ($query) {
                $query->where('status', 'expired')
                      ->orWhere('expires_at', '<=', now());
            })
            ->count();
    }

    /**
     * Get statistics about expired tokens.
     */
    public function getExpiredTokensStats(): array
    {
        $stats = DB::table('scan_login_tokens')
            ->selectRaw('
                COUNT(*) as total,
                MIN(expires_at) as oldest,
                MAX(expires_at) as newest
            ')
            ->where(function ($query) {
                $query->where('status', 'expired')
                      ->orWhere('expires_at', '<=', now());
            })
            ->first();
        
        return [
            'total' => $stats->total ?? 0,
            'oldest' => $stats->oldest ? Carbon::parse($stats->oldest) : null,
            'newest' => $stats->newest ? Carbon::parse($stats->newest) : null,
        ];
    }

    /**
     * Get general token statistics.
     */
    public function getTokenStats(): array
    {
        $now = now()->toDateTimeString();
        $stats = DB::table('scan_login_tokens')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "pending" AND expires_at > ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "used" THEN 1 ELSE 0 END) as used,
                SUM(CASE WHEN status = "expired" OR expires_at <= ? THEN 1 ELSE 0 END) as expired
            ', [$now, $now])
            ->first();

        return [
            'pending' => $stats->pending ?? 0,
            'used' => $stats->used ?? 0,
            'expired' => $stats->expired ?? 0,
            'total' => $stats->total ?? 0,
        ];
    }

    /**
     * Generate a secure random token.
     */
    private function generateSecureToken(): string
    {
        return Str::random(64);
    }

    /**
     * Get token record by token string.
     */
    public function getTokenRecord(string $token): ?ScanLoginToken
    {
        return ScanLoginToken::where('token', $token)->first();
    }

    /**
     * Mark a token as expired.
     */
    public function markAsExpired(string $token): void
    {
        DB::table('scan_login_tokens')
            ->where('token', $token)
            ->update([
                'status' => 'expired',
                'updated_at' => now(),
            ]);
    }

    /**
     * Get token data.
     */
    public function getTokenData(string $token): ?array
    {
        $tokenRecord = DB::table('scan_login_tokens')
            ->where('token', $token)
            ->first();
        
        if (!$tokenRecord) {
            return null;
        }
        
        return [
            'token' => $tokenRecord->token,
            'status' => $tokenRecord->status,
            'created_at' => $tokenRecord->created_at,
            'expires_at' => $tokenRecord->expires_at,
            'used_at' => $tokenRecord->used_at,
            'user_id' => $tokenRecord->user_id,
        ];
    }

    /**
     * Cancel a token.
     */
    public function cancel(string $token): bool
    {
        $updated = DB::table('scan_login_tokens')
            ->where('token', $token)
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);
        
        return $updated > 0;
    }

    /**
     * Check if a token exists and is valid.
     */
    public function exists(string $token): bool
    {
        return $this->validate($token);
    }




}