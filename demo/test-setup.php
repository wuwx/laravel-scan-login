<?php
/**
 * Demo Application Setup and Functionality Test Script
 * 
 * This script tests the complete demo application setup including:
 * - Composer dependencies
 * - Database migrations and seeding
 * - Application startup
 * - QR code generation and login flow
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Wuwx\LaravelScanLogin\Services\QrCodeGenerator;
use Wuwx\LaravelScanLogin\Services\TokenManager;

class DemoSetupTest
{
    private $app;
    private $results = [];
    private $errors = [];

    public function __construct()
    {
        echo "🚀 Starting Demo Application Setup Tests\n";
        echo "=====================================\n\n";
    }

    public function runAllTests()
    {
        $this->testComposerInstall();
        $this->testDatabaseMigrationAndSeeding();
        $this->testApplicationStartup();
        $this->testQrCodeGenerationAndLoginFlow();
        
        $this->displayResults();
        
        return empty($this->errors);
    }

    private function testComposerInstall()
    {
        echo "📦 Testing Composer Install...\n";
        
        try {
            // Check if vendor directory exists
            if (!is_dir(__DIR__ . '/vendor')) {
                throw new Exception('Vendor directory not found. Run composer install first.');
            }

            // Check if composer.lock exists
            if (!file_exists(__DIR__ . '/composer.lock')) {
                throw new Exception('composer.lock not found. Dependencies may not be properly installed.');
            }

            // Check if key Laravel packages are installed
            $requiredPackages = [
                'laravel/framework',
                'wuwx/laravel-scan-login'
            ];

            $composerLock = json_decode(file_get_contents(__DIR__ . '/composer.lock'), true);
            $installedPackages = array_column($composerLock['packages'], 'name');

            foreach ($requiredPackages as $package) {
                if (!in_array($package, $installedPackages)) {
                    throw new Exception("Required package {$package} not found in installed packages.");
                }
            }

            $this->results['composer'] = '✅ Composer install verification passed';
            echo "   ✅ All required packages are installed\n\n";

        } catch (Exception $e) {
            $this->errors['composer'] = $e->getMessage();
            echo "   ❌ Composer test failed: " . $e->getMessage() . "\n\n";
        }
    }

    private function testDatabaseMigrationAndSeeding()
    {
        echo "🗄️  Testing Database Migration and Seeding...\n";
        
        try {
            // Bootstrap Laravel application
            $this->bootstrapApplication();

            // Check if database file exists (SQLite)
            $dbPath = database_path('database.sqlite');
            if (!file_exists($dbPath)) {
                // Create empty database file
                touch($dbPath);
                echo "   📝 Created SQLite database file\n";
            }

            // Run migrations
            Artisan::call('migrate:fresh', ['--force' => true]);
            echo "   🔄 Database migrations completed\n";

            // Check if required tables exist
            $requiredTables = ['users', 'scan_login_tokens', 'password_reset_tokens', 'sessions'];
            foreach ($requiredTables as $table) {
                if (!Schema::hasTable($table)) {
                    throw new Exception("Required table '{$table}' not found after migration.");
                }
            }

            // Run seeders
            Artisan::call('db:seed', ['--force' => true]);
            echo "   🌱 Database seeding completed\n";

            // Verify seeded data
            $userCount = DB::table('users')->count();
            if ($userCount === 0) {
                throw new Exception('No users found after seeding.');
            }

            $this->results['database'] = "✅ Database setup passed ({$userCount} users seeded)";
            echo "   ✅ Database migration and seeding verification passed\n\n";

        } catch (Exception $e) {
            $this->errors['database'] = $e->getMessage();
            echo "   ❌ Database test failed: " . $e->getMessage() . "\n\n";
        }
    }

    private function testApplicationStartup()
    {
        echo "🚀 Testing Application Startup...\n";
        
        try {
            // Test if application can be bootstrapped
            if (!$this->app) {
                $this->bootstrapApplication();
            }

            // Test if key services are registered
            $requiredServices = [
                'auth',
                'db',
                'view',
                'session'
            ];

            foreach ($requiredServices as $service) {
                if (!$this->app->bound($service)) {
                    throw new Exception("Required service '{$service}' not bound in container.");
                }
            }

            // Test if scan login service is available
            if (!$this->app->bound(QrCodeGenerator::class)) {
                throw new Exception('QrCodeGenerator service not available.');
            }

            if (!$this->app->bound(TokenManager::class)) {
                throw new Exception('TokenManager service not available.');
            }

            // Test if routes are loaded
            $router = $this->app['router'];
            $routes = $router->getRoutes();
            
            if ($routes->count() === 0) {
                throw new Exception('No routes loaded.');
            }

            $this->results['startup'] = '✅ Application startup verification passed';
            echo "   ✅ Application can start successfully\n";
            echo "   ✅ All required services are available\n";
            echo "   ✅ Routes are loaded (" . $routes->count() . " routes)\n\n";

        } catch (Exception $e) {
            $this->errors['startup'] = $e->getMessage();
            echo "   ❌ Application startup test failed: " . $e->getMessage() . "\n\n";
        }
    }

    private function testQrCodeGenerationAndLoginFlow()
    {
        echo "📱 Testing QR Code Generation and Login Flow...\n";
        
        try {
            if (!$this->app) {
                $this->bootstrapApplication();
            }

            // Test QR code generation
            $qrGenerator = $this->app->make(QrCodeGenerator::class);
            $tokenManager = $this->app->make(TokenManager::class);

            // Generate a token
            $token = $tokenManager->generateToken();
            if (empty($token)) {
                throw new Exception('Failed to generate login token.');
            }
            echo "   🔑 Login token generated: " . substr($token, 0, 8) . "...\n";

            // Generate QR code
            $qrCode = $qrGenerator->generate($token);
            if (empty($qrCode)) {
                throw new Exception('Failed to generate QR code.');
            }
            echo "   📊 QR code generated successfully\n";

            // Test token validation
            $isValid = $tokenManager->validateToken($token);
            if (!$isValid) {
                throw new Exception('Generated token is not valid.');
            }
            echo "   ✅ Token validation passed\n";

            // Test token retrieval
            $tokenData = $tokenManager->getToken($token);
            if (!$tokenData) {
                throw new Exception('Failed to retrieve token data.');
            }
            echo "   📋 Token data retrieval successful\n";

            // Test user authentication simulation
            $testUser = DB::table('users')->first();
            if (!$testUser) {
                throw new Exception('No test user available for authentication test.');
            }

            // Simulate successful authentication
            $authResult = $tokenManager->authenticateToken($token, $testUser->id);
            if (!$authResult) {
                throw new Exception('Token authentication simulation failed.');
            }
            echo "   🔐 Authentication simulation successful\n";

            $this->results['qr_login'] = '✅ QR code generation and login flow verification passed';
            echo "   ✅ Complete QR code login flow tested successfully\n\n";

        } catch (Exception $e) {
            $this->errors['qr_login'] = $e->getMessage();
            echo "   ❌ QR code login test failed: " . $e->getMessage() . "\n\n";
        }
    }

    private function bootstrapApplication()
    {
        if ($this->app) {
            return;
        }

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
        echo "📊 Test Results Summary\n";
        echo "======================\n\n";

        if (!empty($this->results)) {
            echo "✅ Passed Tests:\n";
            foreach ($this->results as $test => $message) {
                echo "   {$message}\n";
            }
            echo "\n";
        }

        if (!empty($this->errors)) {
            echo "❌ Failed Tests:\n";
            foreach ($this->errors as $test => $error) {
                echo "   {$test}: {$error}\n";
            }
            echo "\n";
        }

        $totalTests = count($this->results) + count($this->errors);
        $passedTests = count($this->results);
        
        echo "Summary: {$passedTests}/{$totalTests} tests passed\n";
        
        if (empty($this->errors)) {
            echo "🎉 All tests passed! The demo application is ready to use.\n";
        } else {
            echo "⚠️  Some tests failed. Please check the errors above.\n";
        }
    }
}

// Run the tests if this script is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new DemoSetupTest();
    $success = $tester->runAllTests();
    exit($success ? 0 : 1);
}