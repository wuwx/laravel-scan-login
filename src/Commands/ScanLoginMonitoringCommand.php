<?php

namespace Wuwx\LaravelScanLogin\Commands;

use Illuminate\Console\Command;
use Wuwx\LaravelScanLogin\Services\MonitoringService;

class ScanLoginMonitoringCommand extends Command
{
    protected $signature = 'scan-login:monitoring 
                            {--hours=24 : Number of hours to include in report}
                            {--format=table : Output format (table, json)}
                            {--metrics : Show only metrics}
                            {--events : Show recent events}
                            {--security : Show security summary}';

    protected $description = 'Display scan login monitoring information';

    public function handle(MonitoringService $monitoringService): int
    {
        $hours = (int) $this->option('hours');
        $format = $this->option('format');

        if ($this->option('metrics')) {
            $this->displayMetrics($monitoringService, $format);
        } elseif ($this->option('events')) {
            $this->displayRecentEvents($monitoringService, $format);
        } elseif ($this->option('security')) {
            $this->displaySecuritySummary($monitoringService, $format);
        } else {
            $this->displayFullReport($monitoringService, $hours, $format);
        }

        return 0;
    }

    private function displayMetrics(MonitoringService $monitoringService, string $format): void
    {
        $metrics = $monitoringService->getMetrics();

        if ($format === 'json') {
            $this->line(json_encode($metrics, JSON_PRETTY_PRINT));
            return;
        }

        $this->info('Scan Login Metrics');
        $this->line('==================');

        $headers = ['Metric', 'Value'];
        $rows = [];

        foreach ($metrics as $key => $value) {
            $displayKey = ucwords(str_replace('_', ' ', $key));
            $displayValue = is_numeric($value) && str_contains($key, 'rate') ? $value . '%' : $value;
            $rows[] = [$displayKey, $displayValue];
        }

        $this->table($headers, $rows);
    }

    private function displayRecentEvents(MonitoringService $monitoringService, string $format): void
    {
        $events = $monitoringService->getRecentEvents(50);

        if ($format === 'json') {
            $this->line(json_encode($events, JSON_PRETTY_PRINT));
            return;
        }

        $this->info('Recent Events (Last 50)');
        $this->line('========================');

        if (empty($events)) {
            $this->line('No recent events found.');
            return;
        }

        $headers = ['Timestamp', 'Event', 'Details'];
        $rows = [];

        foreach (array_reverse($events) as $event) {
            $timestamp = isset($event['timestamp']) ? 
                \Carbon\Carbon::parse($event['timestamp'])->format('Y-m-d H:i:s') : 'Unknown';
            
            $eventType = $event['event'] ?? 'Unknown';
            
            $details = [];
            if (isset($event['success'])) {
                $details[] = 'Success: ' . ($event['success'] ? 'Yes' : 'No');
            }
            if (isset($event['ip_address'])) {
                $details[] = 'IP: ' . $event['ip_address'];
            }
            if (isset($event['user_id'])) {
                $details[] = 'User ID: ' . $event['user_id'];
            }

            $rows[] = [
                $timestamp,
                ucwords(str_replace('_', ' ', $eventType)),
                implode(', ', $details),
            ];
        }

        $this->table($headers, $rows);
    }

    private function displaySecuritySummary(MonitoringService $monitoringService, string $format): void
    {
        $report = $monitoringService->generateReport(24);
        $securitySummary = $report['security_summary'];

        if ($format === 'json') {
            $this->line(json_encode($securitySummary, JSON_PRETTY_PRINT));
            return;
        }

        $this->info('Security Summary (Last 24 Hours)');
        $this->line('=================================');

        $this->line("Failed Logins: {$securitySummary['failed_logins']}");
        $this->line("Security Events: {$securitySummary['security_events']}");

        // Show rate limited IPs/users if any
        // This would require additional implementation in MonitoringService
        $this->line('');
        $this->line('Note: Use scan-login:monitoring --events to see detailed security events');
    }

    private function displayFullReport(MonitoringService $monitoringService, int $hours, string $format): void
    {
        $report = $monitoringService->generateReport($hours);

        if ($format === 'json') {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
            return;
        }

        $this->info("Scan Login Monitoring Report ({$report['period']})");
        $this->line('================================================');
        $this->line("Generated: {$report['generated_at']}");

        // Metrics
        $this->line('');
        $this->info('Metrics:');
        foreach ($report['metrics'] as $key => $value) {
            $displayKey = ucwords(str_replace('_', ' ', $key));
            $displayValue = is_numeric($value) && str_contains($key, 'rate') ? $value . '%' : $value;
            $this->line("  {$displayKey}: {$displayValue}");
        }

        // Events summary
        $this->line('');
        $this->info('Events Summary:');
        $this->line("  Total Events: {$report['event_count']}");
        
        foreach ($report['events_by_type'] as $eventType => $count) {
            $displayType = ucwords(str_replace('_', ' ', $eventType));
            $this->line("  {$displayType}: {$count}");
        }

        // Security summary
        $this->line('');
        $this->info('Security Summary:');
        $this->line("  Failed Logins: {$report['security_summary']['failed_logins']}");
        $this->line("  Security Events: {$report['security_summary']['security_events']}");

        // Recommendations
        $this->line('');
        $this->info('Recommendations:');
        
        $metrics = $report['metrics'];
        if ($metrics['success_rate'] < 80 && $metrics['successful_logins'] + $metrics['failed_logins'] > 10) {
            $this->line("  <fg=yellow>• Low success rate ({$metrics['success_rate']}%) - investigate failed login causes</fg>");
        }
        
        if ($metrics['completion_rate'] < 50 && $metrics['qr_codes_generated'] > 10) {
            $this->line("  <fg=yellow>• Low completion rate ({$metrics['completion_rate']}%) - users may be abandoning the login process</fg>");
        }
        
        if ($report['security_summary']['failed_logins'] > 50) {
            $this->line("  <fg=red>• High number of failed logins - consider implementing additional security measures</fg>");
        }

        if (empty($this->output->getVerbosity()) || $this->output->getVerbosity() === 0) {
            $this->line('');
            $this->line('Use -v for more detailed output or --format=json for machine-readable format');
        }
    }
}