<?php

namespace Wuwx\LaravelScanLogin\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Wuwx\LaravelScanLogin\Exceptions\ScanLoginException;

/**
 * Rate limiting middleware for scan login endpoints
 */
class ScanLoginRateLimitMiddleware
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1')
    {
        $key = $this->resolveRequestSignature($request);
        
        if ($this->limiter->tooManyAttempts($key, (int) $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);
            
            $exception = new ScanLoginException(
                '请求过于频繁，请稍后再试',
                'RATE_LIMIT_EXCEEDED',
                [
                    'retry_after' => $retryAfter,
                    'max_attempts' => $maxAttempts,
                    'decay_minutes' => $decayMinutes,
                ],
                429
            );

            if ($request->expectsJson()) {
                return response()->json($exception->toArray(), 429)
                    ->header('Retry-After', $retryAfter)
                    ->header('X-RateLimit-Limit', $maxAttempts)
                    ->header('X-RateLimit-Remaining', 0);
            }

            return redirect()->back()
                ->withErrors(['scan_login' => $exception->getMessage()])
                ->header('Retry-After', $retryAfter);
        }

        $this->limiter->hit($key, (int) $decayMinutes * 60);

        $response = $next($request);

        // Add rate limit headers to successful responses
        $remaining = $this->limiter->retriesLeft($key, (int) $maxAttempts);
        
        return $response
            ->header('X-RateLimit-Limit', $maxAttempts)
            ->header('X-RateLimit-Remaining', $remaining);
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $route = $request->route();
        $routeName = $route ? $route->getName() : $request->path();
        
        // Use IP address and route for rate limiting
        return sha1(
            $routeName . '|' . $request->ip()
        );
    }
}