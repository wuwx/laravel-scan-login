<?php

namespace Wuwx\LaravelScanLogin\Console\Commands;

use Illuminate\Console\Command;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;

class CleanupExpiredTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan-login:cleanup
                            {--days=7 : Delete tokens older than this many days}
                            {--status= : Only delete tokens with specific status (pending,claimed,consumed,cancelled,expired)}
                            {--force : Force the operation without confirmation}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired scan login tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $status = $this->option('status');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if ($days < 1) {
            $this->error('Days must be at least 1');
            return self::FAILURE;
        }

        $cutoffDate = now()->subDays($days);

        // 构建查询
        $query = ScanLoginToken::where('created_at', '<', $cutoffDate);

        if ($status) {
            $validStatuses = ['pending', 'claimed', 'consumed', 'cancelled', 'expired'];
            if (!in_array($status, $validStatuses)) {
                $this->error("Invalid status. Valid values: " . implode(', ', $validStatuses));
                return self::FAILURE;
            }
            $query->where('state', 'like', "%{$status}%");
        }

        // 统计将要删除的记录
        $count = $query->count();

        if ($count === 0) {
            $this->info('No tokens found to clean up.');
            return self::SUCCESS;
        }

        // 显示详细统计信息
        $this->displayStatistics($cutoffDate, $status);

        // Dry run 模式
        if ($dryRun) {
            $this->warn("DRY RUN: Would delete {$count} token(s).");
            $this->info('No tokens were actually deleted. Remove --dry-run to perform the cleanup.');
            return self::SUCCESS;
        }

        // 确认删除
        if (!$force && !$this->confirm("Do you want to delete {$count} token(s)?", true)) {
            $this->info('Cleanup cancelled.');
            return self::SUCCESS;
        }

        // 执行删除
        $this->info('Cleaning up tokens...');
        
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $deleted = $query->delete();

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✓ Successfully deleted {$deleted} token(s).");

        return self::SUCCESS;
    }

    /**
     * Display statistics about tokens to be deleted.
     */
    protected function displayStatistics($cutoffDate, $status): void
    {
        $this->newLine();
        $this->info('Cleanup Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Cutoff Date', $cutoffDate->format('Y-m-d H:i:s')],
                ['Status Filter', $status ?: 'All'],
                ['Total Tokens', ScanLoginToken::count()],
                ['Tokens to Delete', ScanLoginToken::where('created_at', '<', $cutoffDate)
                    ->when($status, fn($q) => $q->where('state', 'like', "%{$status}%"))
                    ->count()],
            ]
        );
        $this->newLine();
    }
}
