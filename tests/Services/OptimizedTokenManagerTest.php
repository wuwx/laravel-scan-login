<?php

namespace Wuwx\LaravelScanLogin\Tests\Services;

use Wuwx\LaravelScanLogin\Services\TokenManager;
use Wuwx\LaravelScanLogin\Services\PerformanceMonitor;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OptimizedTokenManagerTest extends TestCase
{
    use RefreshDatabase;

    private TokenManager $tokenManager;
    private PerformanceMonitor $performanceMonitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->performanceMonitor = new PerformanceMonitor();
        $this->tokenManager = new TokenManager($this->performanceMonitor);
    }

    public function test_creates_token_and_caches_data(): void
    {
        $token = $this->tokenManager->create();

        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('scan_login_tokens', [
            'token' => $token,
            'status' => 'pending',
        ]);

        // Verify token is cached
        $cacheKey = "scan_login:token:{$token}";
        $cachedData = Cache::get($cacheKey);
        $this->assertNotNull($cachedData);
        $this->assertEquals('pending', $cachedData['status']);
    }

    public function test_validates_token_from_cache(): void
    {
        $token = $this->tokenManager->create();

        // First validation should hit cache
        $isValid = $this->tokenManager->validate($token);
        $this->assertTrue($isValid);

        // Second validation should also hit cache
        $isValid = $this->tokenManager->validate($token);
        $this->assertTrue($isValid);

        $stats = $this->tokenManager->getPerformanceStats();
        $this->assertGreaterThan(0, $stats['validate']['cache_hits']);
    }

    public function test_gets_status_from_cache(): void
    {
        $token = $this->tokenManager->create();

        $status = $this->tokenManager->getStatus($token);
        $this->assertEquals('pending', $status);

        $stats = $this->tokenManager->getPerformanceStats();
        $this->assertGreaterThan(0, $stats['getStatus']['cache_hits']);
    }

    public function test_marks_token_as_used_and_updates_cache(): void
    {
        $token = $this->tokenManager->create();
        $userId = 1;

        $this->tokenManager->markAsUsed($token, $userId);

        // Verify database is updated
        $this->assertDatabaseHas('scan_login_tokens', [
            'token' => $token,
            'status' => 'used',
            'user_id' => $userId,
        ]);

        // Verify cache is updated
        $status = $this->tokenManager->getStatus($token);
        $this->assertEquals('used', $status);

        $retrievedUserId = $this->tokenManager->getUserId($token);
        $this->assertEquals($userId, $retrievedUserId);
    }

    public function test_handles_expired_tokens(): void
    {
        // Create an expired token
        $token = $this->tokenManager->create();
        
        // Manually expire the token in database
        ScanLoginToken::where('token', $token)->update([
            'expires_at' => now()->subMinutes(10),
        ]);

        // Clear cache to force database lookup
        Cache::forget("scan_login:token:{$token}");

        $status = $this->tokenManager->getStatus($token);
        $this->assertEquals('expired', $status);
    }

    public function test_cleanup_removes_expired_tokens_and_cache(): void
    {
        // Create some tokens
        $token1 = $this->tokenManager->create();
        $token2 = $this->tokenManager->create();

        // Expire one token
        ScanLoginToken::where('token', $token1)->update([
            'expires_at' => now()->subMinutes(10),
        ]);

        $deletedCount = $this->tokenManager->cleanupExpiredTokens();

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('scan_login_tokens', ['token' => $token1]);
        $this->assertDatabaseHas('scan_login_tokens', ['token' => $token2]);

        // Verify cache is cleared for expired token
        $cacheKey = "scan_login:token:{$token1}";
        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_warms_up_cache(): void
    {
        // Create some tokens
        $token1 = $this->tokenManager->create();
        $token2 = $this->tokenManager->create();

        // Clear cache
        $this->tokenManager->clearCache();

        // Warm up cache
        $count = $this->tokenManager->warmUpCache();

        $this->assertEquals(2, $count);

        // Verify tokens are cached
        $cacheKey1 = "scan_login:token:{$token1}";
        $cacheKey2 = "scan_login:token:{$token2}";
        
        $this->assertNotNull(Cache::get($cacheKey1));
        $this->assertNotNull(Cache::get($cacheKey2));
    }

    public function test_gets_optimized_token_statistics(): void
    {
        // Create tokens with different statuses
        $token1 = $this->tokenManager->create();
        $token2 = $this->tokenManager->create();
        $token3 = $this->tokenManager->create();

        $this->tokenManager->markAsUsed($token2, 1);
        
        ScanLoginToken::where('token', $token3)->update([
            'expires_at' => now()->subMinutes(10),
        ]);

        $stats = $this->tokenManager->getTokenStats();

        $this->assertEquals(1, $stats['pending']);
        $this->assertEquals(1, $stats['used']);
        $this->assertEquals(1, $stats['expired']);
        $this->assertEquals(3, $stats['total']);
    }

    public function test_performance_monitoring_integration(): void
    {
        $token = $this->tokenManager->create();

        // Clear cache to force database lookup
        Cache::forget("scan_login:token:{$token}");

        // This should record a cache miss and database query
        $this->tokenManager->validate($token);

        // This should record a cache hit
        $this->tokenManager->validate($token);

        $stats = $this->tokenManager->getPerformanceStats();
        
        $this->assertEquals(1, $stats['validate']['cache_hits']);
        $this->assertEquals(1, $stats['validate']['cache_misses']);
        $this->assertEquals(1, $stats['validate']['db_queries']);
        $this->assertGreaterThan(0, $stats['validate']['avg_db_time']);
    }
}