<?php

namespace Wuwx\LaravelScanLogin\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RateLimitService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    /**
     * Check if the request should be rate limited.
     */
    public function shouldLimit(Request $request, string $action): bool
    {
        if (!config('scan-login.rate_limit.enabled', true)) {
            return false;
        }

        $key = $this->buildKey($request, $action);
        $config = $this->getConfigForAction($action);

        if ($this->limiter->tooManyAttempts($key, $config['max_attempts'])) {
            $this->logRateLimitHit($request, $action, $key);
            return true;
        }

        $this->limiter->hit($key, $config['decay_minutes'] * 60);
        return false;
    }

    /**
     * Get remaining attempts for a request.
     */
    public function remainingAttempts(Request $request, string $action): int
    {
        $key = $this->buildKey($request, $action);
        $config = $this->getConfigForAction($action);

        return $this->limiter->retriesLeft($key, $config['max_attempts']);
    }

    /**
     * Get seconds until rate limit resets.
     */
    public function availableIn(Request $request, string $action): int
    {
        $key = $this->buildKey($request, $action);
        return $this->limiter->availableIn($key);
    }

    /**
     * Clear rate limit for a request.
     */
    public function clear(Request $request, string $action): void
    {
        $key = $this->buildKey($request, $action);
        $this->limiter->clear($key);
    }

    /**
     * Build rate limit key.
     */
    protected function buildKey(Request $request, string $action): string
    {
        $strategy = config('scan-login.rate_limit.strategy', 'ip');

        return match ($strategy) {
            'ip' => "scan-login:{$action}:" . $request->ip(),
            'user' => "scan-login:{$action}:user:" . ($request->user()?->id ?? 'guest'),
            'ip_and_user' => "scan-login:{$action}:" . $request->ip() . ':' . ($request->user()?->id ?? 'guest'),
            'session' => "scan-login:{$action}:session:" . $request->session()->getId(),
            default => "scan-login:{$action}:" . $request->ip(),
        };
    }

    /**
     * Get configuration for specific action.
     */
    protected function getConfigForAction(string $action): array
    {
        $defaults = [
            'max_attempts' => config('scan-login.rate_limit.max_attempts', 10),
            'decay_minutes' => config('scan-login.rate_limit.decay_minutes', 1),
        ];

        // 为不同的操作设置不同的限制
        $actionConfigs = config('scan-login.rate_limit.actions', []);

        return array_merge($defaults, $actionConfigs[$action] ?? []);
    }

    /**
     * Log rate limit hit.
     */
    protected function logRateLimitHit(Request $request, string $action, string $key): void
    {
        if (!config('scan-login.rate_limit.log_hits', true)) {
            return;
        }

        Log::warning('Scan login rate limit exceeded', [
            'action' => $action,
            'key' => $key,
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Check if IP is whitelisted.
     */
    public function isWhitelisted(Request $request): bool
    {
        $whitelist = config('scan-login.rate_limit.whitelist', []);
        return in_array($request->ip(), $whitelist);
    }

    /**
     * Check if IP is blacklisted.
     */
    public function isBlacklisted(Request $request): bool
    {
        $blacklist = config('scan-login.rate_limit.blacklist', []);
        return in_array($request->ip(), $blacklist);
    }
}
