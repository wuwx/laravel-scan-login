<?php

namespace Wuwx\LaravelScanLogin\Console\Commands;

use Illuminate\Console\Command;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;

class TokenStatisticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan-login:stats
                            {--days=30 : Show statistics for the last N days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display scan login token statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $startDate = now()->subDays($days);

        $this->info("Scan Login Statistics (Last {$days} days)");
        $this->newLine();

        // 总体统计
        $this->displayOverallStats($startDate);

        // 按状态统计
        $this->displayStatusStats($startDate);

        // 成功率统计
        $this->displaySuccessRate($startDate);

        // 最近的 token
        $this->displayRecentTokens();

        return self::SUCCESS;
    }

    /**
     * Display overall statistics.
     */
    protected function displayOverallStats($startDate): void
    {
        $total = ScanLoginToken::where('created_at', '>=', $startDate)->count();
        $today = ScanLoginToken::whereDate('created_at', today())->count();
        $oldest = ScanLoginToken::oldest()->first();
        $newest = ScanLoginToken::latest()->first();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Tokens', number_format($total)],
                ['Tokens Today', number_format($today)],
                ['Oldest Token', $oldest ? $oldest->created_at->diffForHumans() : 'N/A'],
                ['Newest Token', $newest ? $newest->created_at->diffForHumans() : 'N/A'],
                ['Total in Database', number_format(ScanLoginToken::count())],
            ]
        );
        $this->newLine();
    }

    /**
     * Display statistics by status.
     */
    protected function displayStatusStats($startDate): void
    {
        $this->info('Status Breakdown:');

        $statuses = [
            'Pending' => 'pending',
            'Claimed' => 'claimed',
            'Consumed' => 'consumed',
            'Cancelled' => 'cancelled',
            'Expired' => 'expired',
        ];

        $data = [];
        foreach ($statuses as $label => $status) {
            $count = ScanLoginToken::where('created_at', '>=', $startDate)
                ->where('state', 'like', "%{$status}%")
                ->count();
            $data[] = [$label, number_format($count)];
        }

        $this->table(['Status', 'Count'], $data);
        $this->newLine();
    }

    /**
     * Display success rate.
     */
    protected function displaySuccessRate($startDate): void
    {
        $total = ScanLoginToken::where('created_at', '>=', $startDate)->count();
        
        if ($total === 0) {
            $this->warn('No tokens found in the specified period.');
            return;
        }

        $consumed = ScanLoginToken::where('created_at', '>=', $startDate)
            ->where('state', 'like', '%consumed%')
            ->count();

        $cancelled = ScanLoginToken::where('created_at', '>=', $startDate)
            ->where('state', 'like', '%cancelled%')
            ->count();

        $expired = ScanLoginToken::where('created_at', '>=', $startDate)
            ->where('state', 'like', '%expired%')
            ->count();

        $successRate = $total > 0 ? round(($consumed / $total) * 100, 2) : 0;
        $cancelRate = $total > 0 ? round(($cancelled / $total) * 100, 2) : 0;
        $expireRate = $total > 0 ? round(($expired / $total) * 100, 2) : 0;

        $this->info('Success Metrics:');
        $this->table(
            ['Metric', 'Value', 'Percentage'],
            [
                ['Successful Logins', number_format($consumed), "{$successRate}%"],
                ['Cancelled', number_format($cancelled), "{$cancelRate}%"],
                ['Expired', number_format($expired), "{$expireRate}%"],
            ]
        );
        $this->newLine();
    }

    /**
     * Display recent tokens.
     */
    protected function displayRecentTokens(): void
    {
        $this->info('Recent Tokens (Last 10):');

        $tokens = ScanLoginToken::latest()
            ->limit(10)
            ->get();

        if ($tokens->isEmpty()) {
            $this->warn('No tokens found.');
            return;
        }

        $data = $tokens->map(function ($token) {
            return [
                substr($token->token, 0, 16) . '...',
                $token->state->getDescription(),
                $token->ip_address ?? 'N/A',
                $token->created_at->diffForHumans(),
            ];
        })->toArray();

        $this->table(
            ['Token', 'Status', 'IP Address', 'Created'],
            $data
        );
    }
}
