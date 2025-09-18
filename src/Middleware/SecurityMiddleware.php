<?php

namespace Wuwx\LaravelScanLogin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Security middleware for scan login functionality
 */
class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if HTTPS is required and enforce it
        if (config('scan-login.require_https', true) && !$request->secure() && !$this->isTestingEnvironment()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'HTTPS_REQUIRED',
                    'message' => 'HTTPS is required for scan login functionality',
                    'details' => [],
                ],
            ], 426); // 426 Upgrade Required
        }

        $response = $next($request);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Add HSTS header if using HTTPS
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Add CSP header for scan login pages
        if ($this->isScanLoginRoute($request)) {
            $csp = "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self';";
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }

    /**
     * Check if the current route is a scan login route
     */
    private function isScanLoginRoute(Request $request): bool
    {
        $path = $request->path();
        return str_starts_with($path, 'scan-login') || 
               str_starts_with($path, 'api/scan-login');
    }

    /**
     * Check if we're in testing environment
     */
    protected function isTestingEnvironment(): bool
    {
        return app()->environment('testing');
    }
}