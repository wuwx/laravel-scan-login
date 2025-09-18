<?php

namespace Wuwx\LaravelScanLogin\Services;

use Wuwx\LaravelScanLogin\Services\TokenManager;
use Wuwx\LaravelScanLogin\Services\QrCodeGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Auth\UserProvider;

class ScanLoginService
{
    public function __construct(
        private TokenManager $tokenManager,
        private QrCodeGenerator $qrCodeGenerator,
        private UserProvider $userProvider
    ) {}

    /**
     * Generate a QR code for login.
     */
    public function generateQrCode(): array
    {
        $token = $this->tokenManager->create();
        $qrCodeSvg = $this->qrCodeGenerator->generate($token);
        $loginUrl = $this->qrCodeGenerator->generateLoginUrl($token);
        
        return [
            'success' => true,
            'data' => [
                'token' => $token,
                'qr_code' => $qrCodeSvg,
                'login_url' => $loginUrl,
                'expires_at' => now()->addMinutes(config('scan-login.token_expiry_minutes', 5))->toISOString(),
                'polling_interval' => config('scan-login.polling_interval_seconds', 3),
            ]
        ];
    }

    /**
     * Process mobile login with credentials.
     */
    public function processLogin(string $token, array $credentials): array
    {
        // Validate token first
        if (!$this->tokenManager->validate($token)) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => '登录令牌无效或已过期',
                ]
            ];
        }

        // Validate credentials
        $user = $this->validateCredentials($credentials);
        if (!$user) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => '用户名或密码错误',
                ]
            ];
        }

        // Mark token as used
        $this->tokenManager->markAsUsed($token, $user->getAuthIdentifier());

        return [
            'success' => true,
            'data' => [
                'message' => '登录成功',
                'user' => [
                    'id' => $user->getAuthIdentifier(),
                    'name' => $user->getAuthIdentifierName(),
                ],
                'redirect_url' => config('scan-login.login_success_redirect', '/dashboard'),
            ]
        ];
    }

    /**
     * Check the login status of a token.
     */
    public function checkLoginStatus(string $token): array
    {
        $status = $this->tokenManager->getStatus($token);
        
        switch ($status) {
            case 'pending':
                return [
                    'success' => true,
                    'data' => [
                        'status' => 'pending',
                        'message' => '等待扫码登录',
                    ]
                ];
                
            case 'used':
                $userId = $this->tokenManager->getUserId($token);
                $user = $this->userProvider->retrieveById($userId);
                
                return [
                    'success' => true,
                    'data' => [
                        'status' => 'completed',
                        'message' => '登录成功',
                        'user' => [
                            'id' => $user?->getAuthIdentifier(),
                            'name' => $user?->getAuthIdentifierName(),
                        ],
                        'redirect_url' => config('scan-login.login_success_redirect', '/dashboard'),
                    ]
                ];
                
            case 'expired':
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'TOKEN_EXPIRED',
                        'message' => '登录令牌已过期，请刷新二维码',
                    ]
                ];
                
            case 'not_found':
            default:
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'TOKEN_NOT_FOUND',
                        'message' => '登录令牌不存在',
                    ]
                ];
        }
    }

    /**
     * Clean up expired tokens.
     */
    public function cleanupExpiredTokens(): int
    {
        return $this->tokenManager->cleanup();
    }

    /**
     * Generate QR code with custom options.
     */
    public function generateQrCodeWithOptions(array $options = []): array
    {
        $token = $this->tokenManager->create();
        $qrCode = $this->qrCodeGenerator->generateWithOptions($token, $options);
        $loginUrl = $this->qrCodeGenerator->generateLoginUrl($token);
        
        return [
            'success' => true,
            'data' => [
                'token' => $token,
                'qr_code' => $qrCode,
                'login_url' => $loginUrl,
                'expires_at' => now()->addMinutes(config('scan-login.token_expiry_minutes', 5))->toISOString(),
                'polling_interval' => config('scan-login.polling_interval_seconds', 3),
            ]
        ];
    }

    /**
     * Get QR code as PNG data URL.
     */
    public function generateQrCodePng(): array
    {
        $token = $this->tokenManager->create();
        $qrCodePng = $this->qrCodeGenerator->generatePng($token);
        $loginUrl = $this->qrCodeGenerator->generateLoginUrl($token);
        
        return [
            'success' => true,
            'data' => [
                'token' => $token,
                'qr_code' => $qrCodePng,
                'login_url' => $loginUrl,
                'expires_at' => now()->addMinutes(config('scan-login.token_expiry_minutes', 5))->toISOString(),
                'polling_interval' => config('scan-login.polling_interval_seconds', 3),
            ]
        ];
    }

    /**
     * Validate user credentials.
     */
    private function validateCredentials(array $credentials): mixed
    {
        // Get the user by the identifier (usually email or username)
        $identifier = $credentials[config('auth.providers.users.identifier', 'email')] ?? null;
        if (!$identifier) {
            return null;
        }

        $user = $this->userProvider->retrieveByCredentials($credentials);
        if (!$user) {
            return null;
        }

        // Validate password
        $password = $credentials['password'] ?? null;
        if (!$password || !$this->userProvider->validateCredentials($user, $credentials)) {
            return null;
        }

        return $user;
    }

    /**
     * Check if scan login is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) config('scan-login.enabled', true);
    }

    /**
     * Get scan login configuration.
     */
    public function getConfig(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'token_expiry_minutes' => (int) config('scan-login.token_expiry_minutes', 5),
            'polling_interval_seconds' => (int) config('scan-login.polling_interval_seconds', 3),
            'qr_code_size' => (int) config('scan-login.qr_code_size', 200),
            'login_success_redirect' => (string) config('scan-login.login_success_redirect', '/dashboard'),
        ];
    }
}