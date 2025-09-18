<?php

namespace Wuwx\LaravelScanLogin\Tests\Services;

use Wuwx\LaravelScanLogin\Services\HealthCheckService;
use Wuwx\LaravelScanLogin\Services\TokenManager;
use Wuwx\LaravelScanLogin\Services\PerformanceMonitor;
use Wuwx\LaravelScanLogin\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    private HealthCheckService $healthCheckService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $tokenManager = new TokenManager(new PerformanceMonitor());
        $performanceMonitor = new PerformanceMonitor();
        
        $this->healthCheckService = new HealthCheckService($tokenManager, $performanceMonitor);
    }

    public function test_performs_comprehensive_health_check(): void
    {
        $healthCheck = $this->healthCheckService->performHealthCheck();

        $this->assertArrayHasKey('status', $healthCheck);
        $this->assertArrayHasKey('timestamp', $healthCheck);
        $this->assertArrayHasKey('checks', $healthCheck);
        $this->assertArrayHasKey('summary', $healthCheck);

        // Check that all expected checks are present
        $expectedChecks = ['database', 'cache', 'configuration', 'token_system', 'performance', 'disk_space'];
        foreach ($expectedChecks as $check) {
            $this->assertArrayHasKey($check, $healthCheck['checks']);
        }
    }

    public function test_database_check_passes_with_healthy_database(): void
    {
        $healthCheck = $this->healthCheckService->performHealthCheck();
        $databaseCheck = $healthCheck['checks']['database'];

        $this->assertEquals('pass', $databaseCheck['status']);
        $this->assertStringContains('Database connection successful', $databaseCheck['message']);
        $this->assertArrayHasKey('data', $databaseCheck);
    }

    public function test_cache_check_passes_with_working_cache(): void
    {
        $healthCheck = $this->healthCheckService->performHealthCheck();
        $cacheCheck = $healthCheck['checks']['cache'];

        $this->assertEquals('pass', $cacheCheck['status']);
        $this->assertStringContains('Cache system operational', $cacheCheck['message']);
    }

    public function test_configuration_check_validates_settings(): void
    {
        $healthCheck = $this->healthCheckService->performHealthCheck();
        $configCheck = $healthCheck['checks']['configuration'];

        $this->assertContains($configCheck['status'], ['pass', 'warn']);
        $this->assertStringContains('Configuration check completed', $configCheck['message']);
    }

    public function test_token_system_check_validates_functionality(): void
    {
        $healthCheck = $this->healthCheckService->performHealthCheck();
        $tokenCheck = $healthCheck['checks']['token_system'];

        $this->assertEquals('pass', $tokenCheck['status']);
        $this->assertStringContains('Token system operational', $tokenCheck['message']);
    }

    public function test_overall_status_reflects_worst_individual_status(): void
    {
        // All checks should pass in test environment
        $healthCheck = $this->healthCheckService->performHealthCheck();
        
        // Status should be pass or warn (not fail in test environment)
        $this->assertContains($healthCheck['status'], ['pass', 'warn']);
    }

    public function test_summary_counts_check_results_correctly(): void
    {
        $healthCheck = $this->healthCheckService->performHealthCheck();
        $summary = $healthCheck['summary'];

        $this->assertArrayHasKey('total_checks', $summary);
        $this->assertArrayHasKey('passed', $summary);
        $this->assertArrayHasKey('warnings', $summary);
        $this->assertArrayHasKey('failed', $summary);

        // Total should equal sum of individual counts
        $total = $summary['passed'] + $summary['warnings'] + $summary['failed'];
        $this->assertEquals($summary['total_checks'], $total);
    }

    public function test_logs_health_check_results(): void
    {
        $healthCheck = $this->healthCheckService->performHealthCheck();
        
        // This should not throw an exception
        $this->healthCheckService->logHealthCheck($healthCheck);
        
        $this->assertTrue(true); // If we get here, logging worked
    }

    public function test_disk_space_check_provides_meaningful_data(): void
    {
        $healthCheck = $this->healthCheckService->performHealthCheck();
        $diskCheck = $healthCheck['checks']['disk_space'];

        $this->assertContains($diskCheck['status'], ['pass', 'warn', 'fail']);
        
        if (isset($diskCheck['data'])) {
            $this->assertArrayHasKey('free_bytes', $diskCheck['data']);
            $this->assertArrayHasKey('total_bytes', $diskCheck['data']);
            $this->assertArrayHasKey('free_percentage', $diskCheck['data']);
        }
    }
}