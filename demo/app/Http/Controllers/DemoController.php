<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
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
            
            // Increment QR generation statistics
            cache()->increment('demo_stats_qr_generated');
            
            // Get demo user for testing
            $demoUser = User::where('email', 'demo@example.com')->first();
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'demo_info' => [
                    'instructions' => 'Scan this QR code with your mobile device',
                    'test_credentials' => [
                        'email' => $demoUser ? $demoUser->email : 'demo@example.com',
                        'password' => 'password'
                    ],
                    'demo_user_exists' => $demoUser !== null,
                    'total_users' => User::count()
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
        $user = Auth::user();
        
        return view('demo.dashboard', [
            'user' => $user,
            'demo_stats' => [
                'login_time' => now()->format('Y-m-d H:i:s'),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
            ]
        ]);
    }

    /**
     * Demo logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
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
            'total_users' => User::count(),
            'demo_user_exists' => User::where('email', 'demo@example.com')->exists(),
            'current_user' => Auth::check() ? Auth::user()->email : null,
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
        try {
            // Validate token exists and is valid
            if (!$this->scanLoginService->validateToken($token)) {
                abort(404, 'Invalid or expired demo token');
            }
        } catch (\Exception $e) {
            abort(404, 'Invalid or expired demo token');
        }
        
        // Get demo user for testing
        $demoUser = User::where('email', 'demo@example.com')->first();
        
        return view('demo.mobile-login', [
            'token' => $token,
            'demo_mode' => true,
            'test_credentials' => [
                'email' => $demoUser ? $demoUser->email : 'demo@example.com',
                'password' => 'password'
            ],
            'demo_user_exists' => $demoUser !== null
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
            // Validate credentials against User model
            $user = User::where('email', $request->email)->first();
            
            if (!$user || !Hash::check($request->password, $user->password)) {
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

            // Process login through scan login service with authenticated user
            $success = $this->scanLoginService->processLogin($token, [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ]);

            if ($success) {
                // Increment demo statistics
                cache()->increment('demo_stats_success');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Demo login successful! You can now close this window.',
                    'demo_info' => [
                        'redirect_message' => 'Check your desktop browser - you should be automatically logged in!',
                        'user' => [
                            'name' => $user->name,
                            'email' => $user->email
                        ]
                    ]
                ]);
            } else {
                // Increment failed attempts
                cache()->increment('demo_stats_failed');
                
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'LOGIN_PROCESSING_FAILED',
                        'message' => 'Login credentials were valid but processing failed'
                    ]
                ], 400);
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