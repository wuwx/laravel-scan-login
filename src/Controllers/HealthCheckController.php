<?php

namespace Wuwx\LaravelScanLogin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Wuwx\LaravelScanLogin\Services\HealthCheckService;

class HealthCheckController extends Controller
{
    public function __construct(
        private HealthCheckService $healthCheckService
    ) {}

    /**
     * Perform health check.
     */
    public function check(Request $request): JsonResponse
    {
        $healthCheck = $this->healthCheckService->performHealthCheck();
        
        // Log health check results
        $this->healthCheckService->logHealthCheck($healthCheck);

        // Determine HTTP status code based on health status
        $httpStatus = match ($healthCheck['status']) {
            'fail' => 503, // Service Unavailable
            'warn' => 200, // OK but with warnings
            default => 200, // OK
        };

        return response()->json($healthCheck, $httpStatus);
    }

    /**
     * Simple liveness probe.
     */
    public function liveness(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toISOString(),
            'service' => 'laravel-scan-login',
        ]);
    }

    /**
     * Readiness probe with basic checks.
     */
    public function readiness(): JsonResponse
    {
        try {
            // Basic readiness checks
            $checks = [
                'database' => $this->checkDatabaseConnection(),
                'cache' => $this->checkCacheConnection(),
            ];

            $allReady = collect($checks)->every(fn($check) => $check['ready']);

            $status = $allReady ? 'ready' : 'not_ready';
            $httpStatus = $allReady ? 200 : 503;

            return response()->json([
                'status' => $status,
                'timestamp' => now()->toISOString(),
                'checks' => $checks,
            ], $httpStatus);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'not_ready',
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Check database connection for readiness probe.
     */
    private function checkDatabaseConnection(): array
    {
        try {
            \DB::connection()->getPdo();
            return [
                'ready' => true,
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'ready' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connection for readiness probe.
     */
    private function checkCacheConnection(): array
    {
        try {
            $cacheStore = config('scan-login.cache_store', 'default');
            \Cache::store($cacheStore)->put('health_check', 'test', 1);
            \Cache::store($cacheStore)->forget('health_check');
            
            return [
                'ready' => true,
                'message' => 'Cache connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'ready' => false,
                'message' => 'Cache connection failed: ' . $e->getMessage(),
            ];
        }
    }
}