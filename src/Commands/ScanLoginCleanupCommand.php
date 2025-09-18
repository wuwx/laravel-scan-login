<?php

namespace Wuwx\LaravelScanLogin\Commands;

use Illuminate\Console\Command;
use Wuwx\LaravelScanLogin\Services\TokenManager;
use Wuwx\LaravelScanLogin\Services\PerformanceMonitor;
use Illuminate\Support\Facades\Log;

class ScanLoginCleanupCommand extends Command
{
    protected $signature = 'scan-login:cleanup 
                            {--batch-size= : Number of tokens to delete in each batch}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--stats : Show cleanup statistics}';

    protected $description = 'Clean up expired scan login tokens';

    public function handle(TokenManager $tokenManager, PerformanceMonitor $performanceMonitor): int
    {
        if (!config('scan-login.cleanup_expired_tokens', true)) {
            $this->info('Token cleanup is disabled in configuration.');
            return 0;
        }

        $batchSize = (int) ($this->option('batch-size') ?? config('scan-login.cleanup_batch_size', 1000));
        $isDryRun = $this->option('dry-run');
        $showStats = $this->option('stats');

        if ($showStats) {
            $this->displayCleanupStats($tokenManager);
            return 0;
        }

        $this->info('Starting scan login token cleanup...');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No tokens will be deleted');
        }

        $totalDeleted = 0;
        $iterations = 0;
        $maxIterations = 100; // Prevent infinite loops

        do {
            if ($isDryRun) {
                $expiredCount = $tokenManager->getExpiredTokensCount();
                $deletedCount = min($expiredCount, $batchSize);
                $this->line("Would delete {$deletedCount} expired tokens");
                break;
            }

            // Monitor cleanup performance
            $metrics = $performanceMonitor->monitorCleanupPerformance(function () use ($tokenManager, $batchSize) {
                return $tokenManager->cleanupExpiredTokens($batchSize);
            });

            $deletedCount = $metrics['deleted_count'];
            $totalDeleted += $deletedCount;
            $iterations++;

            if ($deletedCount > 0) {
                $this->line("Deleted {$deletedCount} expired tokens (batch {$iterations})");
                $this->line("Execution time: " . number_format($metrics['execution_time'] * 1000, 2) . "ms");
                $this->line("Memory used: " . $this->formatBytes($metrics['memory_used']));
            }

            // Log cleanup metrics
            if (config('scan-login.enable_logging', true)) {
                Log::info('Scan login token cleanup completed', [
                    'batch' => $iterations,
                    'deleted_count' => $deletedCount,
                    'execution_time' => $metrics['execution_time'],
                    'memory_used' => $metrics['memory_used'],
                ]);
            }

        } while ($deletedCount > 0 && $iterations < $maxIterations);

        if ($totalDeleted > 0) {
            $this->info("Cleanup completed. Total tokens deleted: {$totalDeleted}");
        } else {
            $this->info('No expired tokens found.');
        }

        // Display final statistics
        if ($this->output->isVerbose()) {
            $this->displayTokenStats($tokenManager);
        }

        return 0;
    }

    private function displayCleanupStats(TokenManager $tokenManager): void
    {
        $expiredStats = $tokenManager->getExpiredTokensStats();
        $tokenStats = $tokenManager->getTokenStats();

        $this->info('Scan Login Token Cleanup Statistics');
        $this->line('====================================');

        $this->line("Expired tokens: {$expiredStats['total']}");
        if ($expiredStats['oldest']) {
            $this->line("Oldest expired: {$expiredStats['oldest']->diffForHumans()}");
        }
        if ($expiredStats['newest']) {
            $this->line("Newest expired: {$expiredStats['newest']->diffForHumans()}");
        }

        $this->line('');
        $this->line('Token Statistics:');
        $this->line("  Pending: {$tokenStats['pending']}");
        $this->line("  Used: {$tokenStats['used']}");
        $this->line("  Expired: {$tokenStats['expired']}");
        $this->line("  Total: {$tokenStats['total']}");

        $batchSize = config('scan-login.cleanup_batch_size', 1000);
        $estimatedBatches = ceil($expiredStats['total'] / $batchSize);
        $this->line("Estimated cleanup batches: {$estimatedBatches}");
    }

    private function displayTokenStats(TokenManager $tokenManager): void
    {
        $stats = $tokenManager->getTokenStats();

        $this->line('');
        $this->info('Current Token Statistics:');
        $this->line("Pending: {$stats['pending']}");
        $this->line("Used: {$stats['used']}");
        $this->line("Expired: {$stats['expired']}");
        $this->line("Total: {$stats['total']}");
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}