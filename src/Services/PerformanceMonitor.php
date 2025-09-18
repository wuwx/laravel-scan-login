<?php

namespace Wuwx\LaravelScanLogin\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceMonitor
{
    private string $cachePrefix;
    private string $cacheStore;

    public function __construct()
    {
        $this->cachePrefix = config('scan-login.cache_prefix', 'scan_login');
        $this->cacheStore = config('scan-login.cache_store', 'default');
    }

    /**
     * Record cache hit.
     */
    public function recordCacheHit(string $operation): void
    {
        $this->incrementCounter("cache_hits:{$operation}");
    }

    /**
     * Record cache miss.
     */
    public function recordCacheMiss(string $operation): void
    {
        $this->incrementCounter("cache_misses:{$operation}");
    }

    /**
     * Record database query.
     */
    public function recordDatabaseQuery(string $operation, float $executionTime): void
    {
        $this->incrementCounter("db_queries:{$operation}");
        $this->recordExecutionTime("db_time:{$operation}", $executionTime);
    }

    /**
     * Get cache hit rate for an operation.
     */
    public function getCacheHitRate(string $operation): float
    {
        $hits = $this->getCounter("cache_hits:{$operation}");
        $misses = $this->getCounter("cache_misses:{$operation}");
        $total = $hits + $misses;

        return $total > 0 ? ($hits / $total) * 100 : 0;
    }

    /**
     * Get performance statistics.
     */
    public function getPerformanceStats(): array
    {
        $operations = ['validate', 'getStatus', 'getUserId', 'markAsUsed'];
        $stats = [];

        foreach ($operations as $operation) {
            $stats[$operation] = [
                'cache_hit_rate' => $this->getCacheHitRate($operation),
                'cache_hits' => $this->getCounter("cache_hits:{$operation}"),
                'cache_misses' => $this->getCounter("cache_misses:{$operation}"),
                'db_queries' => $this->getCounter("db_queries:{$operation}"),
                'avg_db_time' => $this->getAverageExecutionTime("db_time:{$operation}"),
            ];
        }

        return $stats;
    }

    /**
     * Get database performance metrics.
     */
    public function getDatabaseMetrics(): array
    {
        // Get table statistics
        $tableStats = DB::select("
            SELECT 
                table_name,
                table_rows,
                data_length,
                index_length,
                (data_length + index_length) as total_size
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = 'scan_login_tokens'
        ");

        // Get index usage statistics (MySQL specific)
        $indexStats = [];
        try {
            $indexStats = DB::select("
                SELECT 
                    index_name,
                    cardinality,
                    sub_part,
                    packed,
                    nullable,
                    index_type
                FROM information_schema.statistics 
                WHERE table_schema = DATABASE() 
                AND table_name = 'scan_login_tokens'
                ORDER BY index_name, seq_in_index
            ");
        } catch (\Exception $e) {
            // Fallback for non-MySQL databases
            Log::debug('Could not retrieve index statistics: ' . $e->getMessage());
        }

        return [
            'table_stats' => $tableStats,
            'index_stats' => $indexStats,
            'slow_queries' => $this->getSlowQueries(),
        ];
    }

    /**
     * Monitor token cleanup performance.
     */
    public function monitorCleanupPerformance(callable $cleanupFunction): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $deletedCount = $cleanupFunction();

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $metrics = [
            'deleted_count' => $deletedCount,
            'execution_time' => $endTime - $startTime,
            'memory_used' => $endMemory - $startMemory,
            'memory_peak' => memory_get_peak_usage(true),
        ];

        // Log performance metrics
        if (config('scan-login.enable_logging', true)) {
            Log::info('Token cleanup performance', $metrics);
        }

        return $metrics;
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        $cacheStore = Cache::store($this->cacheStore);
        
        // Try to get cache statistics if available
        $stats = [
            'store' => $this->cacheStore,
            'prefix' => $this->cachePrefix,
        ];

        // Add Redis-specific stats if using Redis
        if ($this->cacheStore === 'redis' || config('cache.default') === 'redis') {
            try {
                $redis = $cacheStore->getRedis();
                $info = $redis->info();
                
                $stats['redis'] = [
                    'used_memory' => $info['used_memory'] ?? null,
                    'used_memory_human' => $info['used_memory_human'] ?? null,
                    'connected_clients' => $info['connected_clients'] ?? null,
                    'total_commands_processed' => $info['total_commands_processed'] ?? null,
                    'keyspace_hits' => $info['keyspace_hits'] ?? null,
                    'keyspace_misses' => $info['keyspace_misses'] ?? null,
                ];
            } catch (\Exception $e) {
                Log::debug('Could not retrieve Redis statistics: ' . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Reset performance counters.
     */
    public function resetCounters(): void
    {
        $operations = ['validate', 'getStatus', 'getUserId', 'markAsUsed'];
        $metrics = ['cache_hits', 'cache_misses', 'db_queries', 'db_time'];

        foreach ($operations as $operation) {
            foreach ($metrics as $metric) {
                $this->resetCounter("{$metric}:{$operation}");
            }
        }
    }

    /**
     * Increment a counter.
     */
    private function incrementCounter(string $key): void
    {
        $cacheKey = $this->getMetricsCacheKey($key);
        Cache::store($this->cacheStore)->increment($cacheKey, 1);
        
        // Set expiration for metrics (24 hours)
        Cache::store($this->cacheStore)->expire($cacheKey, 86400);
    }

    /**
     * Get counter value.
     */
    private function getCounter(string $key): int
    {
        $cacheKey = $this->getMetricsCacheKey($key);
        return (int) Cache::store($this->cacheStore)->get($cacheKey, 0);
    }

    /**
     * Reset counter.
     */
    private function resetCounter(string $key): void
    {
        $cacheKey = $this->getMetricsCacheKey($key);
        Cache::store($this->cacheStore)->forget($cacheKey);
    }

    /**
     * Record execution time.
     */
    private function recordExecutionTime(string $key, float $time): void
    {
        $cacheKey = $this->getMetricsCacheKey($key);
        $times = Cache::store($this->cacheStore)->get($cacheKey, []);
        
        $times[] = $time;
        
        // Keep only last 100 measurements
        if (count($times) > 100) {
            $times = array_slice($times, -100);
        }
        
        Cache::store($this->cacheStore)->put($cacheKey, $times, 86400);
    }

    /**
     * Get average execution time.
     */
    private function getAverageExecutionTime(string $key): float
    {
        $cacheKey = $this->getMetricsCacheKey($key);
        $times = Cache::store($this->cacheStore)->get($cacheKey, []);
        
        return count($times) > 0 ? array_sum($times) / count($times) : 0;
    }

    /**
     * Get metrics cache key.
     */
    private function getMetricsCacheKey(string $key): string
    {
        return "{$this->cachePrefix}:metrics:{$key}";
    }

    /**
     * Get slow queries (implementation depends on database).
     */
    private function getSlowQueries(): array
    {
        // This is a placeholder - implementation would depend on the database system
        // For MySQL, you could query the slow query log
        // For PostgreSQL, you could use pg_stat_statements
        return [];
    }
}