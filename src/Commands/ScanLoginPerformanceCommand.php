<?php

namespace Wuwx\LaravelScanLogin\Commands;

use Illuminate\Console\Command;
use Wuwx\LaravelScanLogin\Services\TokenManager;
use Wuwx\LaravelScanLogin\Services\PerformanceMonitor;

class ScanLoginPerformanceCommand extends Command
{
    protected $signature = 'scan-login:performance 
                            {--reset : Reset performance counters}
                            {--cache : Show cache statistics}
                            {--database : Show database metrics}
                            {--warm-cache : Warm up the cache with active tokens}';

    protected $description = 'Display and manage scan login performance metrics';

    public function handle(TokenManager $tokenManager, PerformanceMonitor $performanceMonitor): int
    {
        if ($this->option('reset')) {
            $tokenManager->resetPerformanceCounters();
            $this->info('Performance counters have been reset.');
            return 0;
        }

        if ($this->option('warm-cache')) {
            $count = $tokenManager->warmUpCache();
            $this->info("Cache warmed up with {$count} active tokens.");
            return 0;
        }

        $this->displayPerformanceStats($tokenManager);

        if ($this->option('cache')) {
            $this->displayCacheStats($tokenManager);
        }

        if ($this->option('database')) {
            $this->displayDatabaseMetrics($tokenManager);
        }

        return 0;
    }

    private function displayPerformanceStats(TokenManager $tokenManager): void
    {
        $stats = $tokenManager->getPerformanceStats();

        $this->info('Scan Login Performance Statistics');
        $this->line('=====================================');

        $headers = ['Operation', 'Cache Hit Rate', 'Cache Hits', 'Cache Misses', 'DB Queries', 'Avg DB Time (ms)'];
        $rows = [];

        foreach ($stats as $operation => $data) {
            $rows[] = [
                $operation,
                number_format($data['cache_hit_rate'], 2) . '%',
                $data['cache_hits'],
                $data['cache_misses'],
                $data['db_queries'],
                number_format($data['avg_db_time'] * 1000, 2),
            ];
        }

        $this->table($headers, $rows);
    }

    private function displayCacheStats(TokenManager $tokenManager): void
    {
        $stats = $tokenManager->getCacheStats();

        $this->line('');
        $this->info('Cache Statistics');
        $this->line('================');

        $this->line("Store: {$stats['store']}");
        $this->line("Prefix: {$stats['prefix']}");

        if (isset($stats['redis'])) {
            $this->line('');
            $this->info('Redis Statistics:');
            foreach ($stats['redis'] as $key => $value) {
                if ($value !== null) {
                    $this->line("  {$key}: {$value}");
                }
            }
        }
    }

    private function displayDatabaseMetrics(TokenManager $tokenManager): void
    {
        $metrics = $tokenManager->getDatabaseMetrics();

        $this->line('');
        $this->info('Database Metrics');
        $this->line('================');

        if (!empty($metrics['table_stats'])) {
            $tableStats = $metrics['table_stats'][0];
            $this->line("Table: {$tableStats->table_name}");
            $this->line("Rows: " . number_format($tableStats->table_rows));
            $this->line("Data Size: " . $this->formatBytes($tableStats->data_length));
            $this->line("Index Size: " . $this->formatBytes($tableStats->index_length));
            $this->line("Total Size: " . $this->formatBytes($tableStats->total_size));
        }

        if (!empty($metrics['index_stats'])) {
            $this->line('');
            $this->info('Index Statistics:');
            
            $headers = ['Index Name', 'Cardinality', 'Type'];
            $rows = [];
            
            foreach ($metrics['index_stats'] as $index) {
                $rows[] = [
                    $index->index_name,
                    number_format($index->cardinality),
                    $index->index_type,
                ];
            }
            
            $this->table($headers, $rows);
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}