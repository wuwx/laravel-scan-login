<?php

namespace Wuwx\LaravelScanLogin\Commands;

use Illuminate\Console\Command;
use Wuwx\LaravelScanLogin\Services\HealthCheckService;

class ScanLoginHealthCheckCommand extends Command
{
    protected $signature = 'scan-login:health 
                            {--format=table : Output format (table, json)}
                            {--log : Log the health check results}';

    protected $description = 'Perform scan login health check';

    public function handle(HealthCheckService $healthCheckService): int
    {
        $this->info('Performing scan login health check...');

        $healthCheck = $healthCheckService->performHealthCheck();

        if ($this->option('log')) {
            $healthCheckService->logHealthCheck($healthCheck);
            $this->info('Health check results logged.');
        }

        $format = $this->option('format');

        if ($format === 'json') {
            $this->line(json_encode($healthCheck, JSON_PRETTY_PRINT));
        } else {
            $this->displayHealthCheckTable($healthCheck);
        }

        // Return appropriate exit code
        return match ($healthCheck['status']) {
            'fail' => 1,
            'warn' => 0, // Warnings don't cause command failure
            default => 0,
        };
    }

    private function displayHealthCheckTable(array $healthCheck): void
    {
        // Overall status
        $statusColor = match ($healthCheck['status']) {
            'pass' => 'green',
            'warn' => 'yellow',
            'fail' => 'red',
            default => 'white',
        };

        $this->line('');
        $this->line("<fg={$statusColor}>Overall Status: " . strtoupper($healthCheck['status']) . '</fg>');
        $this->line("Timestamp: {$healthCheck['timestamp']}");

        // Summary
        $summary = $healthCheck['summary'];
        $this->line('');
        $this->info('Summary:');
        $this->line("Total Checks: {$summary['total_checks']}");
        $this->line("<fg=green>Passed: {$summary['passed']}</fg>");
        $this->line("<fg=yellow>Warnings: {$summary['warnings']}</fg>");
        $this->line("<fg=red>Failed: {$summary['failed']}</fg>");

        // Individual checks
        $this->line('');
        $this->info('Individual Checks:');
        
        $headers = ['Check', 'Status', 'Message'];
        $rows = [];

        foreach ($healthCheck['checks'] as $checkName => $check) {
            $statusDisplay = match ($check['status']) {
                'pass' => '<fg=green>PASS</fg>',
                'warn' => '<fg=yellow>WARN</fg>',
                'fail' => '<fg=red>FAIL</fg>',
                default => $check['status'],
            };

            $message = $check['message'];
            
            // Add warnings and errors to message
            if (!empty($check['warnings'])) {
                $message .= ' | Warnings: ' . implode(', ', $check['warnings']);
            }
            if (!empty($check['errors'])) {
                $message .= ' | Errors: ' . implode(', ', $check['errors']);
            }

            $rows[] = [
                ucfirst(str_replace('_', ' ', $checkName)),
                $statusDisplay,
                $message,
            ];
        }

        $this->table($headers, $rows);

        // Additional details for failed or warning checks
        foreach ($healthCheck['checks'] as $checkName => $check) {
            if ($check['status'] !== 'pass' && isset($check['data'])) {
                $this->line('');
                $this->line("<fg=cyan>Details for {$checkName}:</fg>");
                $this->line(json_encode($check['data'], JSON_PRETTY_PRINT));
            }
        }
    }
}