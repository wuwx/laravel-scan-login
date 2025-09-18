<?php

namespace Wuwx\LaravelScanLogin\Tests\Services;

use Wuwx\LaravelScanLogin\Services\PerformanceMonitor;
use Wuwx\LaravelScanLogin\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class PerformanceMonitorTest extends TestCase
{
    private PerformanceMonitor $performanceMonitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->performanceMonitor = new PerformanceMonitor();
    }

    public function test_records_cache_hits_and_misses(): void
    {
        $this->performanceMonitor->recordCacheHit('validate');
        $this->performanceMonitor->recordCacheHit('validate');
        $this->performanceMonitor->recordCacheMiss('validate');

        $hitRate = $this->performanceMonitor->getCacheHitRate('validate');
        
        $this->assertEquals(66.67, round($hitRate, 2));
    }

    public function test_records_database_queries(): void
    {
        $this->performanceMonitor->recordDatabaseQuery('getStatus', 0.05);
        $this->performanceMonitor->recordDatabaseQuery('getStatus', 0.03);

        $stats = $this->performanceMonitor->getPerformanceStats();
        
        $this->assertEquals(2, $stats['getStatus']['db_queries']);
        $this->assertEquals(0.04, $stats['getStatus']['avg_db_time']);
    }

    public function test_gets_performance_statistics(): void
    {
        $this->performanceMonitor->recordCacheHit('validate');
        $this->performanceMonitor->recordCacheMiss('validate');
        $this->performanceMonitor->recordDatabaseQuery('validate', 0.02);

        $stats = $this->performanceMonitor->getPerformanceStats();

        $this->assertArrayHasKey('validate', $stats);
        $this->assertEquals(50.0, $stats['validate']['cache_hit_rate']);
        $this->assertEquals(1, $stats['validate']['cache_hits']);
        $this->assertEquals(1, $stats['validate']['cache_misses']);
        $this->assertEquals(1, $stats['validate']['db_queries']);
    }

    public function test_monitors_cleanup_performance(): void
    {
        $cleanupFunction = function () {
            usleep(10000); // 10ms
            return 5; // deleted 5 tokens
        };

        $metrics = $this->performanceMonitor->monitorCleanupPerformance($cleanupFunction);

        $this->assertEquals(5, $metrics['deleted_count']);
        $this->assertGreaterThan(0.01, $metrics['execution_time']);
        $this->assertArrayHasKey('memory_used', $metrics);
        $this->assertArrayHasKey('memory_peak', $metrics);
    }

    public function test_resets_counters(): void
    {
        $this->performanceMonitor->recordCacheHit('validate');
        $this->performanceMonitor->recordCacheMiss('validate');

        $this->performanceMonitor->resetCounters();

        $stats = $this->performanceMonitor->getPerformanceStats();
        $this->assertEquals(0, $stats['validate']['cache_hits']);
        $this->assertEquals(0, $stats['validate']['cache_misses']);
    }

    public function test_gets_cache_statistics(): void
    {
        $stats = $this->performanceMonitor->getCacheStats();

        $this->assertArrayHasKey('store', $stats);
        $this->assertArrayHasKey('prefix', $stats);
        $this->assertEquals('scan_login', $stats['prefix']);
    }
}