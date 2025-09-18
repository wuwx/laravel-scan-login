<?php
/**
 * QR Code Generation and Login Flow Test
 * 
 * This script specifically tests the QR code generation and login flow
 * functionality of the demo application.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Wuwx\LaravelScanLogin\Services\QrCodeGenerator;
use Wuwx\LaravelScanLogin\Services\TokenManager;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;

class QrFlowTest
{
    private $app;
    private $testResults = [];

    public function __construct()
    {
        echo "📱 QR Code Generation and Login Flow Test\n";
        echo "========================================\n\n";
        $this->bootstrapApplication();
    }

    public function runTests()
    {
        $this->testTokenGeneration();
        $this->testQrCodeGeneration();
        $this->testTokenValidation();
        $this->testLoginFlow();
        $this->testTokenExpiration();
        $this->testErrorHandling();
        
        $this->displayResults();
        
        return empty(array_filter($this->testResults, function($result) {
            return !$result['passed'];
        }));
    }

    private function testTokenGeneration()
    {
        echo "🔑 Testing Token Generation...\n";
        
        try {
            $tokenManager = $this->app->make(TokenManager::class);
            
            // Test basic token generation
            $token = $tokenManager->generateToken();
            
            if (empty($token)) {
                throw new Exception('Token generation returned empty result');
            }
            
            if (strlen($token) < 32) {
                throw new Exception('Generated token is too short');
            }
            
            // Test token uniqueness
            $token2 = $tokenManager->generateToken();
            if ($token === $token2) {
                throw new Exception('Generated tokens are not unique');
            }
            
            // Verify token is stored in database
            $tokenRecord = ScanLoginToken::where('token', $token)->first();
            if (!$tokenRecord) {
                throw new Exception('Token not found in database after generation');
            }
            
            $this->testResults['token_generation'] = [
                'passed' => true,
                'message' => 'Token generation successful'
            ];
            
            echo "   ✅ Token generated: " . substr($token, 0, 8) . "...\n";
            echo "   ✅ Token uniqueness verified\n";
            echo "   ✅ Token stored in database\n\n";
            
        } catch (Exception $e) {
            $this->testResults['token_generation'] = [
                'passed' => false,
                'message' => 'Token generation failed: ' . $e->getMessage()
            ];
            echo "   ❌ " . $e->getMessage() . "\n\n";
        }
    }

    private function testQrCodeGeneration()
    {
        echo "📊 Testing QR Code Generation...\n";
        
        try {
            $qrGenerator = $this->app->make(QrCodeGenerator::class);
            $tokenManager = $this->app->make(TokenManager::class);
            
            // Generate a token for QR code
            $token = $tokenManager->generateToken();
            
            // Generate QR code
            $qrCode = $qrGenerator->generate($token);
            
            if (empty($qrCode)) {
                throw new Exception('QR code generation returned empty result');
            }
            
            // Check if QR code contains expected data
            if (!str_contains($qrCode, 'data:image')) {
                throw new Exception('QR code does not appear to be a valid data URI');
            }
            
            // Test QR code with custom size
            $customQrCode = $qrGenerator->generate($token, 150);
            if (empty($customQrCode)) {
                throw new Exception('QR code generation with custom size failed');
            }
            
            $this->testResults['qr_generation'] = [
                'passed' => true,
                'message' => 'QR code generation successful'
            ];
            
            echo "   ✅ QR code generated successfully\n";
            echo "   ✅ QR code is valid data URI\n";
            echo "   ✅ Custom size QR code generation works\n\n";
            
        } catch (Exception $e) {
            $this->testResults['qr_generation'] = [
                'passed' => false,
                'message' => 'QR code generation failed: ' . $e->getMessage()
            ];
            echo "   ❌ " . $e->getMessage() . "\n\n";
        }
    }

    private function testTokenValidation()
    {
        echo "✅ Testing Token Validation...\n";
        
        try {
            $tokenManager = $this->app->make(TokenManager::class);
            
            // Generate a valid token
            $validToken = $tokenManager->generateToken();
            
            // Test valid token
            $isValid = $tokenManager->validateToken($validToken);
            if (!$isValid) {
                throw new Exception('Valid token failed validation');
            }
            
            // Test invalid token
            $invalidToken = 'invalid_token_' . time();
            $isInvalid = $tokenManager->validateToken($invalidToken);
            if ($isInvalid) {
                throw new Exception('Invalid token passed validation');
            }
            
            // Test token retrieval
            $tokenData = $tokenManager->getToken($validToken);
            if (!$tokenData) {
                throw new Exception('Failed to retrieve valid token data');
            }
            
            $this->testResults['token_validation'] = [
                'passed' => true,
                'message' => 'Token validation successful'
            ];
            
            echo "   ✅ Valid token validation passed\n";
            echo "   ✅ Invalid token validation failed as expected\n";
            echo "   ✅ Token data retrieval successful\n\n";
            
        } catch (Exception $e) {
            $this->testResults['token_validation'] = [
                'passed' => false,
                'message' => 'Token validation failed: ' . $e->getMessage()
            ];
            echo "   ❌ " . $e->getMessage() . "\n\n";
        }
    }

    private function testLoginFlow()
    {
        echo "🔐 Testing Login Flow...\n";
        
        try {
            $tokenManager = $this->app->make(TokenManager::class);
            
            // Get a test user
            $testUser = DB::table('users')->first();
            if (!$testUser) {
                throw new Exception('No test user available for login flow test');
            }
            
            // Generate token for login
            $token = $tokenManager->generateToken();
            
            // Simulate authentication
            $authResult = $tokenManager->authenticateToken($token, $testUser->id);
            if (!$authResult) {
                throw new Exception('Token authentication failed');
            }
            
            // Verify token is marked as used
            $tokenRecord = ScanLoginToken::where('token', $token)->first();
            if (!$tokenRecord || !$tokenRecord->used_at) {
                throw new Exception('Token not marked as used after authentication');
            }
            
            // Test that used token cannot be used again
            $secondAuth = $tokenManager->authenticateToken($token, $testUser->id);
            if ($secondAuth) {
                throw new Exception('Used token was accepted for second authentication');
            }
            
            $this->testResults['login_flow'] = [
                'passed' => true,
                'message' => 'Login flow successful'
            ];
            
            echo "   ✅ Token authentication successful\n";
            echo "   ✅ Token marked as used after authentication\n";
            echo "   ✅ Used token rejected for reuse\n\n";
            
        } catch (Exception $e) {
            $this->testResults['login_flow'] = [
                'passed' => false,
                'message' => 'Login flow failed: ' . $e->getMessage()
            ];
            echo "   ❌ " . $e->getMessage() . "\n\n";
        }
    }

    private function testTokenExpiration()
    {
        echo "⏰ Testing Token Expiration...\n";
        
        try {
            $tokenManager = $this->app->make(TokenManager::class);
            
            // Create an expired token by manipulating the database
            $expiredToken = $tokenManager->generateToken();
            
            // Update the token to be expired
            ScanLoginToken::where('token', $expiredToken)
                ->update(['created_at' => now()->subMinutes(10)]);
            
            // Test that expired token is not valid
            $isValid = $tokenManager->validateToken($expiredToken);
            if ($isValid) {
                throw new Exception('Expired token passed validation');
            }
            
            // Test cleanup of expired tokens
            $initialCount = ScanLoginToken::count();
            $tokenManager->cleanupExpiredTokens();
            $afterCleanupCount = ScanLoginToken::count();
            
            if ($afterCleanupCount >= $initialCount) {
                throw new Exception('Expired token cleanup did not remove any tokens');
            }
            
            $this->testResults['token_expiration'] = [
                'passed' => true,
                'message' => 'Token expiration handling successful'
            ];
            
            echo "   ✅ Expired token validation failed as expected\n";
            echo "   ✅ Token cleanup removed expired tokens\n\n";
            
        } catch (Exception $e) {
            $this->testResults['token_expiration'] = [
                'passed' => false,
                'message' => 'Token expiration test failed: ' . $e->getMessage()
            ];
            echo "   ❌ " . $e->getMessage() . "\n\n";
        }
    }

    private function testErrorHandling()
    {
        echo "🚨 Testing Error Handling...\n";
        
        try {
            $tokenManager = $this->app->make(TokenManager::class);
            
            // Test authentication with non-existent user
            $token = $tokenManager->generateToken();
            $authResult = $tokenManager->authenticateToken($token, 99999);
            
            if ($authResult) {
                throw new Exception('Authentication succeeded with non-existent user');
            }
            
            // Test QR generation with invalid token
            $qrGenerator = $this->app->make(QrCodeGenerator::class);
            $qrCode = $qrGenerator->generate('');
            
            if (!empty($qrCode)) {
                throw new Exception('QR code generated for empty token');
            }
            
            $this->testResults['error_handling'] = [
                'passed' => true,
                'message' => 'Error handling successful'
            ];
            
            echo "   ✅ Non-existent user authentication failed as expected\n";
            echo "   ✅ Empty token QR generation failed as expected\n\n";
            
        } catch (Exception $e) {
            $this->testResults['error_handling'] = [
                'passed' => false,
                'message' => 'Error handling test failed: ' . $e->getMessage()
            ];
            echo "   ❌ " . $e->getMessage() . "\n\n";
        }
    }

    private function bootstrapApplication()
    {
        // Create Laravel application instance
        $this->app = new Application(realpath(__DIR__));

        // Load environment variables
        if (file_exists(__DIR__ . '/.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
            $dotenv->load();
        }

        // Bootstrap the application
        $this->app->singleton(
            Illuminate\Contracts\Http\Kernel::class,
            App\Http\Kernel::class
        );

        $this->app->singleton(
            Illuminate\Contracts\Console\Kernel::class,
            App\Console\Kernel::class
        );

        $this->app->singleton(
            Illuminate\Contracts\Debug\ExceptionHandler::class,
            App\Exceptions\Handler::class
        );

        // Bootstrap the kernel
        $kernel = $this->app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
    }

    private function displayResults()
    {
        echo "📊 QR Flow Test Results\n";
        echo "======================\n\n";

        $passed = 0;
        $total = count($this->testResults);

        foreach ($this->testResults as $test => $result) {
            if ($result['passed']) {
                echo "✅ " . ucfirst(str_replace('_', ' ', $test)) . ": " . $result['message'] . "\n";
                $passed++;
            } else {
                echo "❌ " . ucfirst(str_replace('_', ' ', $test)) . ": " . $result['message'] . "\n";
            }
        }

        echo "\nSummary: {$passed}/{$total} tests passed\n";

        if ($passed === $total) {
            echo "🎉 All QR flow tests passed! The scan login functionality is working correctly.\n";
        } else {
            echo "⚠️  Some QR flow tests failed. Please check the implementation.\n";
        }
    }
}

// Run the tests if this script is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new QrFlowTest();
    $success = $tester->runTests();
    exit($success ? 0 : 1);
}