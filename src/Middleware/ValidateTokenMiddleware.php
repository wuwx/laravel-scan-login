<?php

namespace Wuwx\LaravelScanLogin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Wuwx\LaravelScanLogin\Services\TokenManager;

class ValidateTokenMiddleware
{
    public function __construct(
        private TokenManager $tokenManager
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->route('token');
        
        if (!$token || !$this->tokenManager->exists($token)) {
            abort(404, '登录令牌不存在或已失效');
        }

        return $next($request);
    }
}