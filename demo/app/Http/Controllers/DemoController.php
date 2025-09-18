<?php

namespace Demo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Wuwx\LaravelScanLogin\Services\ScanLoginService;
use Wuwx\LaravelScanLogin\Facades\LaravelScanLogin;

class DemoController extends Controller
{
    protected ScanLoginService $scanLoginService;

    public function __construct(ScanLoginService $scanLoginService)
    {
        $this->scanLoginService = $scanLoginService;
    }

    /**
     * Demo home page
     */
    public function index(): View
    {
        return view('demo.index');
    }

    /**
     * Basic scan login demo
     */
    public function basic(): View
    {
        return view('demo.basic');
    }

    /**
     * Custom styled scan login demo
     */
    public function custom(): View
    {
        return view('demo.custom');
    }

    /**
     * API integration demo
     */
    public function api(): View
    {
        return view('demo.api');
    }

    /**
     * Error handling demo
     */
    public function errors(): View
    {
        return view('demo.errors');
    }

    /**
     * Generate QR code for demo
     */
    public function generateQrCode(): JsonResponse
    {
        try {
            $result = $this->scanLoginService->generateQrCode();
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'demo_info' => [
                    'instructions' => 'Scan this QR code with your mobile device',
                    'test_credentials' => [
                        'email' => 'demo@example.com',
                        'password' => 'password'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'QR_GENERATION_FAILED',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Check login status for demo
     */
    public function checkStatus(string $token): JsonResponse
    {
        try {
            $status = $this->scanLoginService->checkLoginStatus($token);
            
            return response()->json([
                'success' => true,
                'data' => $status,
                'demo_info' => [
                    'polling_count' => session('demo_polling_count', 0) + 1
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STATUS_CHECK_FAILED',
                    'message' => $e->getMessage()
                ]
            ], 400);
        }
    }

    /**
     * Simulate error scenarios for demo
     */
    public function simulateError(Request $request): JsonResponse
    {
        $errorType = $request->input('type', 'generic');
        
        $errors = [
            'token_expired' => [
                'code' => 'TOKEN_EXPIRED',
                'message' => 'The login token has expired. Please refresh the QR code.',
                'status' => 400
            ],
            'rate_limited' => [
                'code' => 'RATE_LIMITED',
                'message' => 'Too many requests. Please wait before trying again.',
                'status' => 429
            ],
            'network_error' => [
                'code' => 'NETWORK_ERROR',
                'message' => 'Network connection failed. Please check your internet connection.',
                'status' => 503
            ],
            'invalid_credentials' => [
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid email or password. Please try again.',
                'status' => 401
            ],
            'generic' => [
                'code' => 'DEMO_ERROR',
                'message' => 'This is a simulated error for demonstration purposes.',
                'status' => 500
            ]
        ];
        
        $error = $errors[$errorType] ?? $errors['generic'];
        
        return response()->json([
            'success' => false,
            'error' => $error,
            'demo_info' => [
                'simulated' => true,
                'error_type' => $errorType
            ]
        ], $error['status']);
    }

    /**
     * Demo dashboard (after successful login)
     */
    public function dashboard(): View
    {
        return view('demo.dashboard');
    }

    /**
     * Demo logout
     */
    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('demo.index')->with('message', 'Successfully logged out');
    }

    /**
     * Show demo statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_demos_run' => cache()->get('demo_stats_total', 0),
            'successful_logins' => cache()->get('demo_stats_success', 0),
            'failed_attempts' => cache()->get('demo_stats_failed', 0),
            'qr_codes_generated' => cache()->get('demo_stats_qr_generated', 0),
            'unique_visitors' => cache()->get('demo_stats_visitors', 0),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Reset demo environment
     */
    public function reset(): JsonResponse
    {
        // Clear demo-related cache
        cache()->forget('demo_stats_total');
        cache()->forget('demo_stats_success');
        cache()->forget('demo_stats_failed');
        cache()->forget('demo_stats_qr_generated');
        
        // Clear any demo sessions
        session()->forget('demo_polling_count');
        
        return response()->json([
            'success' => true,
            'message' => 'Demo environment has been reset'
        ]);
    }

    /**
     * Show mobile demo login page
     */
    public function showMobileDemo(string $token): View
    {
        // Validate token exists
        if (!$this->scanLoginService->validateToken($token)) {
            abort(404, 'Invalid or expired demo token');
        }
        
        return view('demo.mobile-login', [
            'token' => $token,
            'demo_mode' => true,
            'test_credentials' => [
                'email' => 'demo@example.com',
                'password' => 'password'
            ]
        ]);
    }

    /**
     * Process mobile demo login
     */
    public function processMobileDemo(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $success = $this->scanLoginService->processLogin($token, [
                'email' => $request->email,
                'password' => $request->password,
            ]);

            if ($success) {
                // Increment demo statistics
                cache()->increment('demo_stats_success');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Demo login successful! You can now close this window.',
                    'demo_info' => [
                        'redirect_message' => 'Check your desktop browser - you should be automatically logged in!'
                    ]
                ]);
            } else {
                // Increment failed attempts
                cache()->increment('demo_stats_failed');
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_CREDENTIALS',
                        'message' => 'Invalid email or password. Try: demo@example.com / password'
                    ],
                    'demo_info' => [
                        'hint' => 'Use the test credentials: demo@example.com / password'
                    ]
                ], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'LOGIN_FAILED',
                    'message' => $e->getMessage()
                ]
            ], 400);
        }
    }
}