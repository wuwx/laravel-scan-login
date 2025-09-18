<?php

namespace Wuwx\LaravelScanLogin\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class MonitoringService
{
    private string $cachePrefix;
    private string $logChannel;

    public function __construct()
    {
        $this->cachePrefix = config('scan-login.cache_prefix', 'scan_login');
        $this->logChannel = config('scan-login.log_channel', 'default');
    }

    /**
     * Log QR code generation event.
     */
    public function logQrCodeGeneration(string $token, Request $request = null): void
    {
        if (!config('scan-login.enable_logging', true)) {
            return;
        }

        $context = [
            'event' => 'qr_code_generated',
            'token' => substr($token, 0, 8) . '...', // Log only first 8 characters for security
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel($this->logChannel)->info('QR code generated', $context);
        
        $this->incrementMetric('qr_codes_generated');
        $this->recordEvent('qr_code_generation', $context);
    }

    /**
     * Log mobile login attempt.
     */
    public function logMobileLoginAttempt(string $token, array $credentials, bool $success, Request $request = null): void
    {
        if (!config('scan-login.enable_logging', true)) {
            return;
        }

        $context = [
            'event' => 'mobile_login_attempt',
            'token' => substr($token, 0, 8) . '...',
            'success' => $success,
            'username' => $credentials['email'] ?? $credentials['username'] ?? 'unknown',
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        if ($success) {
            Log::channel($this->logChannel)->info('Mobile login successful', $context);
            $this->incrementMetric('successful_logins');
        } else {
            Log::channel($this->logChannel)->warning('Mobile login failed', $context);
            $this->incrementMetric('failed_logins');
            
            // Track failed attempts for security monitoring
            $this->trackFailedAttempt($request?->ip(), $credentials['email'] ?? $credentials['username'] ?? 'unknown');
        }

        $this->recordEvent('mobile_login_attempt', $context);
    }

    /**
     * Log desktop login completion.
     */
    public function logDesktopLoginCompletion(string $token, int $userId, Request $request = null): void
    {
        if (!config('scan-login.enable_logging', true)) {
            return;
        }

        $context = [
            'event' => 'desktop_login_completed',
            'token' => substr($token, 0, 8) . '...',
            'user_id' => $userId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel($this->logChannel)->info('Desktop login completed', $context);
        
        $this->incrementMetric('completed_logins');
        $this->recordEvent('desktop_login_completion', $context);
    }

    /**
     * Log security events.
     */
    public function logSecurityEvent(string $eventType, array $context, string $level = 'warning'): void
    {
        if (!config('scan-login.enable_logging', true)) {
            return;
        }

        $context['event'] = $eventType;
        $context['timestamp'] = now()->toISOString();

        Log::channel($this->logChannel)->log($level, "Security event: {$eventType}", $context);
        
        $this->incrementMetric('security_events');
        $this->recordEvent('security_event', $context);
    }

    /**
     * Log token cleanup events.
     */
    public function logTokenCleanup(int $deletedCount, float $executionTime, int $memoryUsed): void
    {
        if (!config('scan-login.enable_logging', true)) {
            return;
        }

        $context = [
            'event' => 'token_cleanup',
            'deleted_count' => $deletedCount,
            'execution_time' => $executionTime,
            'memory_used' => $memoryUsed,
            'timestamp' => now()->toISOString(),
        ];

        Log::channel($this->logChannel)->info('Token cleanup completed', $context);
        
        $this->recordEvent('token_cleanup', $context);
    }

    /**
     * Get monitoring metrics.
     */
    public function getMetrics(): array
    {
        $metrics = [
            'qr_codes_generated' => $this->getMetric('qr_codes_generated'),
            'successful_logins' => $this->getMetric('successful_logins'),
            'failed_logins' => $this->getMetric('failed_logins'),
            'completed_logins' => $this->getMetric('completed_logins'),
            'security_events' => $this->getMetric('security_events'),
        ];

        // Calculate derived metrics
        $totalAttempts = $metrics['successful_logins'] + $metrics['failed_logins'];
        $metrics['success_rate'] = $totalAttempts > 0 ? 
            round(($metrics['successful_logins'] / $totalAttempts) * 100, 2) : 0;

        $metrics['completion_rate'] = $metrics['qr_codes_generated'] > 0 ? 
            round(($metrics['completed_logins'] / $metrics['qr_codes_generated']) * 100, 2) : 0;

        return $metrics;
    }

    /**
     * Get recent events.
     */
    public function getRecentEvents(int $limit = 100): array
    {
        $cacheKey = $this->getCacheKey('recent_events');
        $events = Cache::get($cacheKey, []);
        
        return array_slice($events, -$limit);
    }

    /**
     * Get failed login attempts for security monitoring.
     */
    public function getFailedAttempts(string $identifier = null): array
    {
        if ($identifier) {
            $cacheKey = $this->getCacheKey("failed_attempts:{$identifier}");
            return Cache::get($cacheKey, []);
        }

        // Get all failed attempts (this is a simplified implementation)
        $cacheKey = $this->getCacheKey('all_failed_attempts');
        return Cache::get($cacheKey, []);
    }

    /**
     * Check if IP or user is rate limited due to failed attempts.
     */
    public function isRateLimited(string $identifier): bool
    {
        $attempts = $this->getFailedAttempts($identifier);
        $maxAttempts = config('scan-login.max_login_attempts', 3);
        $windowMinutes = 15; // Rate limit window

        $recentAttempts = collect($attempts)->filter(function ($attempt) use ($windowMinutes) {
            return now()->diffInMinutes($attempt['timestamp']) <= $windowMinutes;
        });

        return $recentAttempts->count() >= $maxAttempts;
    }

    /**
     * Reset failed attempts for an identifier.
     */
    public function resetFailedAttempts(string $identifier): void
    {
        $cacheKey = $this->getCacheKey("failed_attempts:{$identifier}");
        Cache::forget($cacheKey);
    }

    /**
     * Generate monitoring report.
     */
    public function generateReport(int $hours = 24): array
    {
        $metrics = $this->getMetrics();
        $recentEvents = $this->getRecentEvents();
        
        // Filter events by time window
        $cutoff = now()->subHours($hours);
        $filteredEvents = collect($recentEvents)->filter(function ($event) use ($cutoff) {
            return isset($event['timestamp']) && $cutoff->lessThan($event['timestamp']);
        });

        return [
            'period' => "{$hours} hours",
            'generated_at' => now()->toISOString(),
            'metrics' => $metrics,
            'event_count' => $filteredEvents->count(),
            'events_by_type' => $filteredEvents->groupBy('event')->map->count(),
            'security_summary' => [
                'failed_logins' => $filteredEvents->where('event', 'mobile_login_attempt')
                    ->where('success', false)->count(),
                'security_events' => $filteredEvents->where('event', 'security_event')->count(),
            ],
        ];
    }

    /**
     * Increment a metric counter.
     */
    private function incrementMetric(string $metric): void
    {
        $cacheKey = $this->getCacheKey("metric:{$metric}");
        Cache::increment($cacheKey, 1);
        
        // Set expiration for metrics (30 days)
        Cache::expire($cacheKey, 30 * 24 * 60 * 60);
    }

    /**
     * Get metric value.
     */
    private function getMetric(string $metric): int
    {
        $cacheKey = $this->getCacheKey("metric:{$metric}");
        return (int) Cache::get($cacheKey, 0);
    }

    /**
     * Record an event for monitoring.
     */
    private function recordEvent(string $eventType, array $context): void
    {
        $cacheKey = $this->getCacheKey('recent_events');
        $events = Cache::get($cacheKey, []);
        
        $events[] = array_merge($context, [
            'event' => $eventType,
            'timestamp' => now()->toISOString(),
        ]);
        
        // Keep only last 1000 events
        if (count($events) > 1000) {
            $events = array_slice($events, -1000);
        }
        
        Cache::put($cacheKey, $events, 24 * 60 * 60); // 24 hours
    }

    /**
     * Track failed login attempts for security monitoring.
     */
    private function trackFailedAttempt(string $ip = null, string $username = null): void
    {
        if ($ip) {
            $this->addFailedAttempt($ip, 'ip');
        }
        
        if ($username) {
            $this->addFailedAttempt($username, 'username');
        }
    }

    /**
     * Add a failed attempt record.
     */
    private function addFailedAttempt(string $identifier, string $type): void
    {
        $cacheKey = $this->getCacheKey("failed_attempts:{$identifier}");
        $attempts = Cache::get($cacheKey, []);
        
        $attempts[] = [
            'type' => $type,
            'identifier' => $identifier,
            'timestamp' => now()->toISOString(),
        ];
        
        // Keep only last 50 attempts
        if (count($attempts) > 50) {
            $attempts = array_slice($attempts, -50);
        }
        
        Cache::put($cacheKey, $attempts, 24 * 60 * 60); // 24 hours
    }

    /**
     * Get cache key with prefix.
     */
    private function getCacheKey(string $key): string
    {
        return "{$this->cachePrefix}:monitoring:{$key}";
    }
}