<?php

namespace Wuwx\LaravelScanLogin\Services;

use Wuwx\LaravelScanLogin\Services\TokenManager;
use Wuwx\LaravelScanLogin\Services\PerformanceMonitor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class HealthCheckService
{
    public function __construct(
        private TokenManager $tokenManager,
        private PerformanceMonitor $performanceMonitor
    ) {}

    /**
     * Perform comprehensive health check.
     */
    public function performHealthCheck(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'configuration' => $this->checkConfiguration(),
            'token_system' => $this->checkTokenSystem(),
            'performance' => $this->checkPerformance(),
            'disk_space' => $this->checkDiskSpace(),
        ];

        $overallStatus = $this->determineOverallStatus($checks);

        return [
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'summary' => $this->generateSummary($checks),
        ];
    }

    /**
     * Check database connectivity and table status.
     */
    private function checkDatabase(): array
    {
        try {
            // Test database connection
            DB::connection()->getPdo();
            
            // Check if scan_login_tokens table exists
            $tableExists = DB::getSchemaBuilder()->hasTable('scan_login_tokens');
            
            if (!$tableExists) {
                return [
                    'status' => 'fail',
                    'message' => 'scan_login_tokens table does not exist',
                ];
            }

            // Check table statistics
            $stats = $this->tokenManager->getTokenStats();
            
            // Check for excessive expired tokens
            $expiredRatio = $stats['total'] > 0 ? ($stats['expired'] / $stats['total']) * 100 : 0;
            
            $status = 'pass';
            $warnings = [];
            
            if ($expiredRatio > 50) {
                $warnings[] = "High ratio of expired tokens: {$expiredRatio}%";
                $status = 'warn';
            }
            
            if ($stats['total'] > 100000) {
                $warnings[] = "Large number of tokens in database: {$stats['total']}";
                $status = 'warn';
            }

            return [
                'status' => $status,
                'message' => 'Database connection successful',
                'data' => $stats,
                'warnings' => $warnings,
            ];

        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache system status.
     */
    private function checkCache(): array
    {
        try {
            $cacheStore = config('scan-login.cache_store', 'default');
            $testKey = 'scan_login:health_check:' . time();
            $testValue = 'test_value';

            // Test cache write
            Cache::store($cacheStore)->put($testKey, $testValue, 60);
            
            // Test cache read
            $retrievedValue = Cache::store($cacheStore)->get($testKey);
            
            // Clean up test key
            Cache::store($cacheStore)->forget($testKey);

            if ($retrievedValue !== $testValue) {
                return [
                    'status' => 'fail',
                    'message' => 'Cache read/write test failed',
                ];
            }

            $cacheStats = $this->performanceMonitor->getCacheStats();

            return [
                'status' => 'pass',
                'message' => 'Cache system operational',
                'data' => $cacheStats,
            ];

        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Cache system error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check configuration validity.
     */
    private function checkConfiguration(): array
    {
        $warnings = [];
        $errors = [];

        // Check critical configuration values
        $tokenExpiry = config('scan-login.token_expiry_minutes');
        if ($tokenExpiry < 1 || $tokenExpiry > 60) {
            $errors[] = "Invalid token expiry time: {$tokenExpiry} minutes";
        }

        $pollingInterval = config('scan-login.polling_interval_seconds');
        if ($pollingInterval < 1 || $pollingInterval > 30) {
            $warnings[] = "Polling interval may impact performance: {$pollingInterval} seconds";
        }

        // Check HTTPS requirement in production
        if (app()->environment('production') && !config('scan-login.require_https', true)) {
            $warnings[] = 'HTTPS is not required in production environment';
        }

        // Check cleanup configuration
        if (!config('scan-login.cleanup_expired_tokens', true)) {
            $warnings[] = 'Automatic token cleanup is disabled';
        }

        $status = 'pass';
        if (!empty($errors)) {
            $status = 'fail';
        } elseif (!empty($warnings)) {
            $status = 'warn';
        }

        return [
            'status' => $status,
            'message' => 'Configuration check completed',
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Check token system functionality.
     */
    private function checkTokenSystem(): array
    {
        try {
            // Test token creation
            $token = $this->tokenManager->create();
            
            // Test token validation
            $isValid = $this->tokenManager->validate($token);
            
            // Test token status
            $status = $this->tokenManager->getStatus($token);
            
            // Clean up test token
            DB::table('scan_login_tokens')->where('token', $token)->delete();
            Cache::forget("scan_login:token:{$token}");

            if (!$isValid || $status !== 'pending') {
                return [
                    'status' => 'fail',
                    'message' => 'Token system functionality test failed',
                ];
            }

            return [
                'status' => 'pass',
                'message' => 'Token system operational',
            ];

        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Token system error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check performance metrics.
     */
    private function checkPerformance(): array
    {
        $stats = $this->performanceMonitor->getPerformanceStats();
        $warnings = [];

        foreach ($stats as $operation => $data) {
            // Check cache hit rate
            if ($data['cache_hit_rate'] < 80 && $data['cache_hits'] + $data['cache_misses'] > 10) {
                $warnings[] = "Low cache hit rate for {$operation}: {$data['cache_hit_rate']}%";
            }

            // Check average database time
            if ($data['avg_db_time'] > 0.1) { // 100ms
                $warnings[] = "Slow database queries for {$operation}: " . 
                             number_format($data['avg_db_time'] * 1000, 2) . "ms";
            }
        }

        $status = empty($warnings) ? 'pass' : 'warn';

        return [
            'status' => $status,
            'message' => 'Performance metrics checked',
            'data' => $stats,
            'warnings' => $warnings,
        ];
    }

    /**
     * Check disk space.
     */
    private function checkDiskSpace(): array
    {
        try {
            $databasePath = database_path();
            $freeBytes = disk_free_space($databasePath);
            $totalBytes = disk_total_space($databasePath);
            
            if ($freeBytes === false || $totalBytes === false) {
                return [
                    'status' => 'warn',
                    'message' => 'Could not determine disk space',
                ];
            }

            $freePercentage = ($freeBytes / $totalBytes) * 100;
            
            $status = 'pass';
            $warnings = [];
            
            if ($freePercentage < 10) {
                $status = 'fail';
                $warnings[] = 'Critical: Less than 10% disk space remaining';
            } elseif ($freePercentage < 20) {
                $status = 'warn';
                $warnings[] = 'Warning: Less than 20% disk space remaining';
            }

            return [
                'status' => $status,
                'message' => 'Disk space check completed',
                'data' => [
                    'free_bytes' => $freeBytes,
                    'total_bytes' => $totalBytes,
                    'free_percentage' => round($freePercentage, 2),
                ],
                'warnings' => $warnings,
            ];

        } catch (Exception $e) {
            return [
                'status' => 'warn',
                'message' => 'Disk space check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Determine overall health status.
     */
    private function determineOverallStatus(array $checks): string
    {
        $hasFailures = false;
        $hasWarnings = false;

        foreach ($checks as $check) {
            if ($check['status'] === 'fail') {
                $hasFailures = true;
                break;
            }
            if ($check['status'] === 'warn') {
                $hasWarnings = true;
            }
        }

        if ($hasFailures) {
            return 'fail';
        }
        if ($hasWarnings) {
            return 'warn';
        }
        return 'pass';
    }

    /**
     * Generate health check summary.
     */
    private function generateSummary(array $checks): array
    {
        $summary = [
            'total_checks' => count($checks),
            'passed' => 0,
            'warnings' => 0,
            'failed' => 0,
        ];

        foreach ($checks as $check) {
            switch ($check['status']) {
                case 'pass':
                    $summary['passed']++;
                    break;
                case 'warn':
                    $summary['warnings']++;
                    break;
                case 'fail':
                    $summary['failed']++;
                    break;
            }
        }

        return $summary;
    }

    /**
     * Log health check results.
     */
    public function logHealthCheck(array $healthCheck): void
    {
        if (!config('scan-login.enable_logging', true)) {
            return;
        }

        $logLevel = match ($healthCheck['status']) {
            'fail' => 'error',
            'warn' => 'warning',
            default => 'info',
        };

        Log::log($logLevel, 'Scan login health check completed', [
            'status' => $healthCheck['status'],
            'summary' => $healthCheck['summary'],
            'timestamp' => $healthCheck['timestamp'],
        ]);

        // Log individual check failures and warnings
        foreach ($healthCheck['checks'] as $checkName => $check) {
            if ($check['status'] === 'fail') {
                Log::error("Scan login health check failed: {$checkName}", $check);
            } elseif ($check['status'] === 'warn') {
                Log::warning("Scan login health check warning: {$checkName}", $check);
            }
        }
    }
}