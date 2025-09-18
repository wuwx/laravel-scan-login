<?php

namespace Wuwx\LaravelScanLogin\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Wuwx\LaravelScanLogin\Services\TokenManager;

class ValidateTokenMiddleware
{
    public function __construct(
        private TokenManager $tokenManager
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $token = $this->extractToken($request);
        
        if (!$token) {
            return $this->tokenNotFoundResponse($request);
        }

        $status = $this->tokenManager->getStatus($token);
        
        switch ($status) {
            case 'not_found':
                return $this->tokenNotFoundResponse($request);
                
            case 'expired':
                return $this->tokenExpiredResponse($request);
                
            case 'used':
                return $this->tokenAlreadyUsedResponse($request);
                
            case 'pending':
                // Token is valid, continue with the request
                return $next($request);
                
            default:
                return $this->invalidTokenResponse($request);
        }
    }

    /**
     * Extract token from the request.
     */
    private function extractToken(Request $request): ?string
    {
        // Try to get token from route parameter first
        $token = $request->route('token');
        
        if (!$token) {
            // Try to get token from query parameter
            $token = $request->query('token');
        }
        
        if (!$token) {
            // Try to get token from request body
            $token = $request->input('token');
        }
        
        return $token;
    }

    /**
     * Return token not found response.
     */
    private function tokenNotFoundResponse(Request $request): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_NOT_FOUND',
                    'message' => '登录令牌不存在',
                ]
            ], 404);
        }

        return response()->view('scan-login::errors.token-not-found', [], 404);
    }

    /**
     * Return token expired response.
     */
    private function tokenExpiredResponse(Request $request): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => '登录令牌已过期，请刷新二维码',
                ]
            ], 410);
        }

        return response()->view('scan-login::errors.token-expired', [], 410);
    }

    /**
     * Return token already used response.
     */
    private function tokenAlreadyUsedResponse(Request $request): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_ALREADY_USED',
                    'message' => '登录令牌已被使用',
                ]
            ], 410);
        }

        return response()->view('scan-login::errors.token-used', [], 410);
    }

    /**
     * Return invalid token response.
     */
    private function invalidTokenResponse(Request $request): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => '登录令牌无效',
                ]
            ], 400);
        }

        return response()->view('scan-login::errors.invalid-token', [], 400);
    }
}