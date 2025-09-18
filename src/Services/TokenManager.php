<?php

namespace Wuwx\LaravelScanLogin\Services;

use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\PerformanceMonitor;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TokenManager
{
    private string $cachePrefix;
    private string $cacheStore;
    private int $cacheTtl;
    private PerformanceMonitor $performanceMonitor;

    public function __construct(PerformanceMonitor $performanceMonitor = null)
    {
        $this->cachePrefix = config('scan-login.cache_prefix', 'scan_login');
        $this->cacheStore = config('scan-login.cache_store', 'default');
        $this->cacheTtl = config('scan-login.token_expiry_minutes', 5) * 60; // Convert to seconds
        $this->performanceMonitor = $performanceMonitor ?? new PerformanceMonitor();
    }

    /**
     * Create a new login token.
     */
    public function create(): string
    {
        $token = $this->generateSecureToken();
        $expiryMinutes = config('scan-login.token_expiry_minutes', 5);
        $expiresAt = now()->addMinutes($expiryMinutes);
        
        $tokenRecord = ScanLoginToken::create([
            'token' => $token,
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);

        // Cache the token data for faster access
        $this->cacheTokenData($token, [
            'id' => $tokenRecord->id,
            'status' => 'pending',
            'user_id' => null,
            'expires_at' => $expiresAt->timestamp,
            'used_at' => null,
        ]);

        return $token;
    }

    /**
     * Validate if a token exists and is valid.
     */
    public function validate(string $token): bool
    {
        $tokenData = $this->getTokenDataFromCache($token, 'validate');
        
        if (!$tokenData) {
            return false;
        }

        return $tokenData['status'] === 'pending' && $tokenData['expires_at'] > time();
    }

    /**
     * Mark a token as used by a specific user.
     */
    public function markAsUsed(string $token, int $userId): void
    {
        $tokenData = $this->getTokenDataFromCache($token);
        
        if ($tokenData && $tokenData['status'] === 'pending' && $tokenData['expires_at'] > time()) {
            // Update database
            DB::table('scan_login_tokens')
                ->where('token', $token)
                ->where('status', 'pending')
                ->update([
                    'status' => 'used',
                    'user_id' => $userId,
                    'used_at' => now(),
                    'updated_at' => now(),
                ]);

            // Update cache
            $tokenData['status'] = 'used';
            $tokenData['user_id'] = $userId;
            $tokenData['used_at'] = time();
            $this->cacheTokenData($token, $tokenData);
        }
    }

    /**
     * Get the status of a token.
     */
    public function getStatus(string $token): string
    {
        $tokenData = $this->getTokenDataFromCache($token, 'getStatus');
        
        if (!$tokenData) {
            return 'not_found';
        }

        if ($tokenData['expires_at'] <= time()) {
            // Mark as expired if it's past expiry time but still pending
            if ($tokenData['status'] === 'pending') {
                $this->markAsExpired($token);
            }
            return 'expired';
        }

        return $tokenData['status'];
    }

    /**
     * Get the user ID associated with a used token.
     */
    public function getUserId(string $token): ?int
    {
        $tokenData = $this->getTokenDataFromCache($token, 'getUserId');
        
        if ($tokenData && $tokenData['status'] === 'used') {
            return $tokenData['user_id'];
        }
        
        return null;
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
        
        // Get expired tokens to clear from cache
        $expiredTokens = DB::table('scan_login_tokens')
            ->where(function ($query) {
                $query->where('status', 'expired')
                      ->orWhere('expires_at', '<=', now());
            })
            ->limit($batchSize)
            ->pluck('token');

        // Clear from cache
        foreach ($expiredTokens as $token) {
            $this->forgetTokenFromCache($token);
        }

        // Delete from database using optimized query
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
        $stats = DB::table('scan_login_tokens')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "pending" AND expires_at > NOW() THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "used" THEN 1 ELSE 0 END) as used,
                SUM(CASE WHEN status = "expired" OR expires_at <= NOW() THEN 1 ELSE 0 END) as expired
            ')
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
        // Update database
        DB::table('scan_login_tokens')
            ->where('token', $token)
            ->update([
                'status' => 'expired',
                'updated_at' => now(),
            ]);

        // Update cache
        $tokenData = $this->getTokenDataFromCache($token);
        if ($tokenData) {
            $tokenData['status'] = 'expired';
            $this->cacheTokenData($token, $tokenData);
        }
    }

    /**
     * Cache token data for faster access.
     */
    private function cacheTokenData(string $token, array $data): void
    {
        $cacheKey = $this->getCacheKey($token);
        Cache::store($this->cacheStore)->put($cacheKey, $data, $this->cacheTtl);
    }

    /**
     * Get token data from cache, fallback to database if not found.
     */
    private function getTokenDataFromCache(string $token, string $operation = 'unknown'): ?array
    {
        $cacheKey = $this->getCacheKey($token);
        $cachedData = Cache::store($this->cacheStore)->get($cacheKey);

        if ($cachedData) {
            $this->performanceMonitor->recordCacheHit($operation);
            return $cachedData;
        }

        $this->performanceMonitor->recordCacheMiss($operation);

        // Fallback to database
        $startTime = microtime(true);
        $tokenRecord = DB::table('scan_login_tokens')
            ->where('token', $token)
            ->first();
        $executionTime = microtime(true) - $startTime;

        $this->performanceMonitor->recordDatabaseQuery($operation, $executionTime);

        if (!$tokenRecord) {
            return null;
        }

        $data = [
            'id' => $tokenRecord->id,
            'status' => $tokenRecord->status,
            'user_id' => $tokenRecord->user_id,
            'expires_at' => Carbon::parse($tokenRecord->expires_at)->timestamp,
            'used_at' => $tokenRecord->used_at ? Carbon::parse($tokenRecord->used_at)->timestamp : null,
        ];

        // Cache for future requests
        $this->cacheTokenData($token, $data);

        return $data;
    }

    /**
     * Remove token from cache.
     */
    private function forgetTokenFromCache(string $token): void
    {
        $cacheKey = $this->getCacheKey($token);
        Cache::store($this->cacheStore)->forget($cacheKey);
    }

    /**
     * Generate cache key for token.
     */
    private function getCacheKey(string $token): string
    {
        return "{$this->cachePrefix}:token:{$token}";
    }

    /**
     * Warm up cache with active tokens.
     */
    public function warmUpCache(): int
    {
        $activeTokens = DB::table('scan_login_tokens')
            ->where('expires_at', '>', now())
            ->whereIn('status', ['pending', 'used'])
            ->get();

        $count = 0;
        foreach ($activeTokens as $tokenRecord) {
            $data = [
                'id' => $tokenRecord->id,
                'status' => $tokenRecord->status,
                'user_id' => $tokenRecord->user_id,
                'expires_at' => Carbon::parse($tokenRecord->expires_at)->timestamp,
                'used_at' => $tokenRecord->used_at ? Carbon::parse($tokenRecord->used_at)->timestamp : null,
            ];

            $this->cacheTokenData($tokenRecord->token, $data);
            $count++;
        }

        return $count;
    }

    /**
     * Clear all cached tokens.
     */
    public function clearCache(): void
    {
        // This is a simple implementation - in production you might want to use cache tags
        // or maintain a list of cached tokens for more efficient clearing
        $tokens = DB::table('scan_login_tokens')
            ->pluck('token');

        foreach ($tokens as $token) {
            $this->forgetTokenFromCache($token);
        }
    }

    /**
     * Get performance statistics.
     */
    public function getPerformanceStats(): array
    {
        return $this->performanceMonitor->getPerformanceStats();
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        return $this->performanceMonitor->getCacheStats();
    }

    /**
     * Get database metrics.
     */
    public function getDatabaseMetrics(): array
    {
        return $this->performanceMonitor->getDatabaseMetrics();
    }

    /**
     * Reset performance counters.
     */
    public function resetPerformanceCounters(): void
    {
        $this->performanceMonitor->resetCounters();
    }
}