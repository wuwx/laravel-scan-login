<?php

namespace Wuwx\LaravelScanLogin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Wuwx\LaravelScanLogin\Exceptions\ScanLoginException;

/**
 * CSRF protection middleware for scan login functionality
 */
class ScanLoginCsrfMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip CSRF check for GET requests and API routes
        if ($this->shouldSkipCsrfCheck($request)) {
            return $next($request);
        }

        // Verify CSRF token
        if (!$this->tokensMatch($request)) {
            $exception = new ScanLoginException(
                'CSRF token mismatch. Please refresh the page and try again.',
                'CSRF_TOKEN_MISMATCH',
                [],
                419
            );

            if ($request->expectsJson()) {
                return response()->json($exception->toArray(), 419);
            }

            return redirect()->back()
                ->withErrors(['scan_login' => $exception->getMessage()])
                ->withInput();
        }

        return $next($request);
    }

    /**
     * Determine if the request should skip CSRF verification
     */
    protected function shouldSkipCsrfCheck(Request $request): bool
    {
        // Skip for GET, HEAD, OPTIONS requests
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }

        // Skip for API routes (they should use other authentication methods)
        if ($request->is('api/*')) {
            return true;
        }

        // Skip in testing environment
        if ($this->isTestingEnvironment()) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the session and input CSRF tokens match
     */
    protected function tokensMatch(Request $request): bool
    {
        $token = $this->getTokenFromRequest($request);
        
        return is_string($request->session()->token()) &&
               is_string($token) &&
               hash_equals($request->session()->token(), $token);
    }

    /**
     * Get the CSRF token from the request
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (!$token && $header = $request->header('X-XSRF-TOKEN')) {
            $token = $this->decryptCookieToken($header);
        }

        return $token;
    }

    /**
     * Decrypt the CSRF token from cookie
     */
    protected function decryptCookieToken(string $token): string
    {
        try {
            return decrypt($token, false);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Check if we're in testing environment
     */
    protected function isTestingEnvironment(): bool
    {
        return app()->environment('testing');
    }
}