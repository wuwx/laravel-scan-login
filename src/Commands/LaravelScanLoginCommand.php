<?php

namespace Wuwx\LaravelScanLogin\Commands;

use Illuminate\Console\Command;
use Wuwx\LaravelScanLogin\Services\TokenManager;
use Wuwx\LaravelScanLogin\Services\ConfigValidator;

class LaravelScanLoginCommand extends Command
{
    public $signature = 'scan-login:cleanup 
                        {--dry-run : Show what would be deleted without actually deleting}
                        {--batch-size= : Number of tokens to delete in each batch}
                        {--force : Force cleanup without confirmation}';

    public $description = 'Clean up expired scan login tokens';

    protected TokenManager $tokenManager;

    public function __construct(TokenManager $tokenManager)
    {
        parent::__construct();
        $this->tokenManager = $tokenManager;
    }

    public function handle(): int
    {
        if (!config('scan-login.enabled', true)) {
            $this->error('Scan login is disabled in configuration.');
            return self::FAILURE;
        }

        // Validate configuration
        try {
            ConfigValidator::getValidatedConfig();
        } catch (\Exception $e) {
            $this->error('Configuration validation failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $batchSize = $this->option('batch-size') ?: config('scan-login.cleanup_batch_size', 1000);
        $force = $this->option('force');

        $this->info('Scan Login Token Cleanup');
        $this->line('');

        // Get count of expired tokens
        $expiredCount = $this->tokenManager->getExpiredTokensCount();

        if ($expiredCount === 0) {
            $this->info('No expired tokens found.');
            return self::SUCCESS;
        }

        $this->info("Found {$expiredCount} expired tokens to clean up.");

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No tokens will be deleted.');
            $this->displayExpiredTokensInfo();
            return self::SUCCESS;
        }

        if (!$force && !$this->confirm("Are you sure you want to delete {$expiredCount} expired tokens?")) {
            $this->info('Cleanup cancelled.');
            return self::SUCCESS;
        }

        // Perform cleanup
        $this->info('Starting cleanup...');
        $progressBar = $this->output->createProgressBar($expiredCount);
        $progressBar->start();

        $deletedCount = 0;
        $batches = ceil($expiredCount / $batchSize);

        for ($i = 0; $i < $batches; $i++) {
            $batchDeleted = $this->tokenManager->cleanupExpiredTokens($batchSize);
            $deletedCount += $batchDeleted;
            $progressBar->advance($batchDeleted);

            if ($batchDeleted === 0) {
                break; // No more tokens to delete
            }
        }

        $progressBar->finish();
        $this->line('');
        $this->info("Successfully deleted {$deletedCount} expired tokens.");

        // Display cleanup statistics
        $this->displayCleanupStats($deletedCount, $expiredCount);

        return self::SUCCESS;
    }

    /**
     * Display information about expired tokens without deleting them.
     */
    protected function displayExpiredTokensInfo(): void
    {
        $stats = $this->tokenManager->getExpiredTokensStats();

        $this->table(
            ['Status', 'Count', 'Oldest', 'Newest'],
            [
                [
                    'Expired',
                    $stats['total'],
                    $stats['oldest'] ? $stats['oldest']->format('Y-m-d H:i:s') : 'N/A',
                    $stats['newest'] ? $stats['newest']->format('Y-m-d H:i:s') : 'N/A',
                ]
            ]
        );
    }

    /**
     * Display cleanup statistics.
     */
    protected function displayCleanupStats(int $deletedCount, int $totalExpired): void
    {
        $this->line('');
        $this->info('Cleanup Statistics:');
        $this->line("- Tokens deleted: {$deletedCount}");
        $this->line("- Total expired found: {$totalExpired}");
        
        if ($deletedCount < $totalExpired) {
            $remaining = $totalExpired - $deletedCount;
            $this->warn("- Remaining expired tokens: {$remaining}");
            $this->line('  (Run the command again to clean up remaining tokens)');
        }

        // Show current token counts
        $currentStats = $this->tokenManager->getTokenStats();
        $this->line('');
        $this->info('Current Token Statistics:');
        $this->line("- Pending tokens: {$currentStats['pending']}");
        $this->line("- Used tokens: {$currentStats['used']}");
        $this->line("- Expired tokens: {$currentStats['expired']}");
        $this->line("- Total tokens: {$currentStats['total']}");
    }
}
