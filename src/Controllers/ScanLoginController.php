<?php

namespace Wuwx\LaravelScanLogin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Routing\Controller;
use Wuwx\LaravelScanLogin\Services\ScanLoginService;
use Wuwx\LaravelScanLogin\Services\MonitoringService;

class ScanLoginController extends Controller
{
    public function __construct(
        private ScanLoginService $scanLoginService,
        private MonitoringService $monitoringService
    ) {}

    /**
     * Generate QR code for desktop login.
     */
    public function generateQrCode(Request $request): JsonResponse
    {
        if (!$this->scanLoginService->isEnabled()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FEATURE_DISABLED',
                    'message' => '扫码登录功能已禁用',
                ]
            ], 403);
        }

        try {
            $result = $this->scanLoginService->generateQrCode();
            
            // Log QR code generation
            if ($result['success']) {
                $this->monitoringService->logQrCodeGeneration($result['data']['token'], $request);
            }
            
            return response()->json($result);
        } catch (\Exception $e) {
            // Log security event for QR generation failure
            $this->monitoringService->logSecurityEvent('qr_generation_failed', [
                'error' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ], 'error');
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'QR_GENERATION_FAILED',
                    'message' => '二维码生成失败，请稍后重试',
                ]
            ], 500);
        }
    }

    /**
     * Check login status for polling.
     */
    public function checkStatus(string $token): JsonResponse
    {
        if (!$this->scanLoginService->isEnabled()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FEATURE_DISABLED',
                    'message' => '扫码登录功能已禁用',
                ]
            ], 403);
        }

        try {
            $result = $this->scanLoginService->checkLoginStatus($token);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STATUS_CHECK_FAILED',
                    'message' => '状态检查失败，请稍后重试',
                ]
            ], 500);
        }
    }

    /**
     * Show mobile login page.
     */
    public function showMobileLogin(string $token): View
    {
        $config = $this->scanLoginService->getConfig();
        
        return view(config('scan-login.mobile_login_view', 'scan-login::mobile-login'), [
            'token' => $token,
            'config' => $config,
        ]);
    }

    /**
     * Process mobile login form submission.
     */
    public function processMobileLogin(Request $request, string $token): JsonResponse
    {
        if (!$this->scanLoginService->isEnabled()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FEATURE_DISABLED',
                    'message' => '扫码登录功能已禁用',
                ]
            ], 403);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:1',
        ]);

        $credentials = $request->only(['email', 'password']);

        // Check for rate limiting
        if ($this->monitoringService->isRateLimited($request->ip()) || 
            $this->monitoringService->isRateLimited($credentials['email'])) {
            
            $this->monitoringService->logSecurityEvent('rate_limit_exceeded', [
                'ip_address' => $request->ip(),
                'email' => $credentials['email'],
                'token' => substr($token, 0, 8) . '...',
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => '登录尝试过于频繁，请稍后重试',
                ]
            ], 429);
        }

        try {
            $result = $this->scanLoginService->processLogin($token, $credentials);
            
            // Log the login attempt
            $this->monitoringService->logMobileLoginAttempt(
                $token, 
                $credentials, 
                $result['success'], 
                $request
            );
            
            // Reset failed attempts on successful login
            if ($result['success']) {
                $this->monitoringService->resetFailedAttempts($request->ip());
                $this->monitoringService->resetFailedAttempts($credentials['email']);
            }
            
            return response()->json($result);
        } catch (\Exception $e) {
            // Log failed login attempt
            $this->monitoringService->logMobileLoginAttempt($token, $credentials, false, $request);
            
            // Log security event for processing failure
            $this->monitoringService->logSecurityEvent('login_processing_failed', [
                'error' => $e->getMessage(),
                'ip_address' => $request->ip(),
                'email' => $credentials['email'],
                'token' => substr($token, 0, 8) . '...',
            ], 'error');
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'LOGIN_PROCESSING_FAILED',
                    'message' => '登录处理失败，请稍后重试',
                ]
            ], 500);
        }
    }
}