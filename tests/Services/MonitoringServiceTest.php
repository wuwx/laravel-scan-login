<?php

namespace Wuwx\LaravelScanLogin\Tests\Services;

use Wuwx\LaravelScanLogin\Services\MonitoringService;
use Wuwx\LaravelScanLogin\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MonitoringServiceTest extends TestCase
{
    private MonitoringService $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitoringService = new MonitoringService();
    }

    public function test_logs_qr_code_generation(): void
    {
        $token = 'test_token_12345';
        $request = Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        $this->monitoringService->logQrCodeGeneration($token, $request);

        $metrics = $this->monitoringService->getMetrics();
        $this->assertEquals(1, $metrics['qr_codes_generated']);
    }

    public function test_logs_mobile_login_attempts(): void
    {
        $token = 'test_token_12345';
        $credentials = ['email' => 'test@example.com', 'password' => 'password'];
        $request = Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        // Log successful login
        $this->monitoringService->logMobileLoginAttempt($token, $credentials, true, $request);
        
        // Log failed login
        $this->monitoringService->logMobileLoginAttempt($token, $credentials, false, $request);

        $metrics = $this->monitoringService->getMetrics();
        $this->assertEquals(1, $metrics['successful_logins']);
        $this->assertEquals(1, $metrics['failed_logins']);
        $this->assertEquals(50.0, $metrics['success_rate']);
    }

    public function test_logs_desktop_login_completion(): void
    {
        $token = 'test_token_12345';
        $userId = 1;
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        $this->monitoringService->logDesktopLoginCompletion($token, $userId, $request);

        $metrics = $this->monitoringService->getMetrics();
        $this->assertEquals(1, $metrics['completed_logins']);
    }

    public function test_logs_security_events(): void
    {
        $eventType = 'suspicious_activity';
        $context = ['ip' => '127.0.0.1', 'reason' => 'multiple_failed_attempts'];

        $this->monitoringService->logSecurityEvent($eventType, $context);

        $metrics = $this->monitoringService->getMetrics();
        $this->assertEquals(1, $metrics['security_events']);
    }

    public function test_calculates_metrics_correctly(): void
    {
        // Generate some test data
        $token = 'test_token_12345';
        $credentials = ['email' => 'test@example.com', 'password' => 'password'];

        // 2 QR codes generated
        $this->monitoringService->logQrCodeGeneration($token);
        $this->monitoringService->logQrCodeGeneration($token . '_2');

        // 3 successful logins, 1 failed
        $this->monitoringService->logMobileLoginAttempt($token, $credentials, true);
        $this->monitoringService->logMobileLoginAttempt($token, $credentials, true);
        $this->monitoringService->logMobileLoginAttempt($token, $credentials, true);
        $this->monitoringService->logMobileLoginAttempt($token, $credentials, false);

        // 1 completed login
        $this->monitoringService->logDesktopLoginCompletion($token, 1);

        $metrics = $this->monitoringService->getMetrics();

        $this->assertEquals(2, $metrics['qr_codes_generated']);
        $this->assertEquals(3, $metrics['successful_logins']);
        $this->assertEquals(1, $metrics['failed_logins']);
        $this->assertEquals(1, $metrics['completed_logins']);
        $this->assertEquals(75.0, $metrics['success_rate']); // 3/4 * 100
        $this->assertEquals(50.0, $metrics['completion_rate']); // 1/2 * 100
    }

    public function test_tracks_failed_attempts_for_rate_limiting(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'wrong'];
        $request = Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);

        // Log multiple failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->monitoringService->logMobileLoginAttempt('token_' . $i, $credentials, false, $request);
        }

        $isRateLimited = $this->monitoringService->isRateLimited('192.168.1.100');
        $this->assertTrue($isRateLimited);

        $failedAttempts = $this->monitoringService->getFailedAttempts('192.168.1.100');
        $this->assertCount(5, $failedAttempts);
    }

    public function test_resets_failed_attempts(): void
    {
        $credentials = ['email' => 'test@example.com', 'password' => 'wrong'];
        $request = Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);

        // Log failed attempts
        $this->monitoringService->logMobileLoginAttempt('token_1', $credentials, false, $request);
        $this->monitoringService->logMobileLoginAttempt('token_2', $credentials, false, $request);

        $this->assertTrue($this->monitoringService->isRateLimited('192.168.1.100'));

        // Reset failed attempts
        $this->monitoringService->resetFailedAttempts('192.168.1.100');

        $this->assertFalse($this->monitoringService->isRateLimited('192.168.1.100'));
    }

    public function test_generates_monitoring_report(): void
    {
        // Generate some test data
        $this->monitoringService->logQrCodeGeneration('token_1');
        $this->monitoringService->logMobileLoginAttempt('token_1', ['email' => 'test@example.com'], true);
        $this->monitoringService->logDesktopLoginCompletion('token_1', 1);

        $report = $this->monitoringService->generateReport(24);

        $this->assertArrayHasKey('period', $report);
        $this->assertArrayHasKey('generated_at', $report);
        $this->assertArrayHasKey('metrics', $report);
        $this->assertArrayHasKey('event_count', $report);
        $this->assertArrayHasKey('events_by_type', $report);
        $this->assertArrayHasKey('security_summary', $report);

        $this->assertEquals('24 hours', $report['period']);
        $this->assertGreaterThan(0, $report['event_count']);
    }

    public function test_gets_recent_events(): void
    {
        // Log some events
        $this->monitoringService->logQrCodeGeneration('token_1');
        $this->monitoringService->logMobileLoginAttempt('token_1', ['email' => 'test@example.com'], true);

        $events = $this->monitoringService->getRecentEvents(10);

        $this->assertIsArray($events);
        $this->assertGreaterThan(0, count($events));

        // Check event structure
        foreach ($events as $event) {
            $this->assertArrayHasKey('event', $event);
            $this->assertArrayHasKey('timestamp', $event);
        }
    }
}