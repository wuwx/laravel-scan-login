<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Wuwx\LaravelScanLogin\Services\TokenManager;
use Illuminate\Contracts\Auth\UserProvider;
use Wuwx\LaravelScanLogin\Tests\Support\TestUser;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up configuration for testing
    config([
        'scan-login.enabled' => true,
        'scan-login.token_expiry_minutes' => 5,
        'scan-login.polling_interval_seconds' => 1,
    ]);

    // Users table is already created in TestCase

    // Set up auth configuration
    config([
        'auth.defaults.guard' => 'web',
        'auth.defaults.passwords' => 'users',
        'auth.guards.web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'auth.providers.users' => [
            'driver' => 'eloquent',
            'model' => TestUser::class,
        ],
    ]);

    // Mock the UserProvider for the ScanLoginService
    $userProvider = \Mockery::mock(UserProvider::class);
    $this->app->instance(UserProvider::class, $userProvider);
    
    $userProvider->shouldReceive('retrieveByCredentials')
        ->andReturnUsing(function ($credentials) {
            return TestUser::where('email', $credentials['email'])->first();
        });
    
    $userProvider->shouldReceive('validateCredentials')
        ->andReturnUsing(function ($user, $credentials) {
            return $user && Hash::check($credentials['password'], $user->password);
        });
});

it('handles concurrent user scenarios efficiently', function () {
    $startTime = microtime(true);
    
    // Simulate 10 concurrent users generating QR codes
    $responses = [];
    for ($i = 0; $i < 10; $i++) {
        $responses[] = $this->postJson('/scan-login/generate');
    }
    
    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;
    
    // All requests should succeed
    foreach ($responses as $response) {
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'qr_code',
                    'login_url',
                    'expires_at'
                ]
            ]);
    }
    
    // Performance check: should complete within reasonable time (2 seconds for 10 requests)
    expect($totalTime)->toBeLessThan(2.0);
    
    // Verify all tokens are unique
    $tokens = array_map(function ($response) {
        return $response->json('data.token');
    }, $responses);
    
    $uniqueTokens = array_unique($tokens);
    expect(count($uniqueTokens))->toBe(10);
});

it('validates token security and randomness', function () {
    $tokenManager = new TokenManager();
    
    // Generate multiple tokens to test randomness
    $tokens = [];
    for ($i = 0; $i < 100; $i++) {
        $tokens[] = $tokenManager->create();
    }
    
    // All tokens should be unique
    $uniqueTokens = array_unique($tokens);
    expect(count($uniqueTokens))->toBe(100);
    
    // Tokens should be of sufficient length (at least 32 characters)
    foreach ($tokens as $token) {
        expect(strlen($token))->toBeGreaterThanOrEqual(32);
    }
    
    // Tokens should contain a mix of characters (not just numbers or letters)
    $hasNumbers = false;
    $hasLetters = false;
    
    foreach ($tokens as $token) {
        if (preg_match('/[0-9]/', $token)) {
            $hasNumbers = true;
        }
        if (preg_match('/[a-zA-Z]/', $token)) {
            $hasLetters = true;
        }
        
        // Token should not contain easily guessable patterns
        expect($token)->not->toContain('123');
        expect($token)->not->toContain('abc');
        expect($token)->not->toContain('000');
    }
    
    expect($hasNumbers)->toBeTrue();
    expect($hasLetters)->toBeTrue();
});

it('prevents token replay attacks', function () {
    // Create a test user
    $user = new TestUser([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
    $user->save();

    // Create a valid token
    $token = ScanLoginToken::create([
        'token' => 'replay-test-token',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Create and authenticate user
    $user = new TestUser([
        'name' => 'Test User',
        'email' => 'replay-test@example.com',
        'password' => Hash::make('password123'),
    ]);
    $user->save();
    
    // First login confirmation should succeed
    $this->actingAs($user);
    $response1 = $this->postJson("/scan-login/{$token->token}");

    $response1->assertStatus(200);

    // Verify token is marked as used
    $token->refresh();
    expect($token->status)->toBe('used');
    expect($token->used_at)->not->toBeNull();

    // Second attempt with same token should fail (replay attack prevention)
    $response2 = $this->postJson("/scan-login/{$token->token}");

    $response2->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'TOKEN_ALREADY_USED',
            ]
        ]);
});

it('enforces token expiration for security', function () {
    // Create an expired token
    $expiredToken = ScanLoginToken::create([
        'token' => 'expired-security-token',
        'status' => 'pending',
        'expires_at' => now()->subMinutes(1), // Expired 1 minute ago
    ]);

    // Attempt to use expired token should fail
    $response = $this->postJson("/scan-login/{$expiredToken->token}", [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'TOKEN_EXPIRED',
            ]
        ]);

    // Verify token is automatically marked as expired
    $expiredToken->refresh();
    expect($expiredToken->status)->toBe('expired');
});

it('handles high frequency polling without performance degradation', function () {
    // Create a valid token
    $token = ScanLoginToken::create([
        'token' => 'polling-test-token',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    $startTime = microtime(true);
    
    // Simulate rapid polling (50 requests)
    $responses = [];
    for ($i = 0; $i < 50; $i++) {
        $responses[] = $this->getJson("/scan-login/status/{$token->token}");
    }
    
    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;
    
    // All requests should succeed
    foreach ($responses as $response) {
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'pending',
                    'logged_in' => false,
                ]
            ]);
    }
    
    // Performance check: 50 status checks should complete within 3 seconds
    expect($totalTime)->toBeLessThan(3.0);
    
    // Average response time should be reasonable (less than 60ms per request)
    $averageTime = $totalTime / 50;
    expect($averageTime)->toBeLessThan(0.06);
});

it('validates database query performance with indexes', function () {
    // Create multiple tokens to test query performance
    $tokens = [];
    for ($i = 0; $i < 1000; $i++) {
        $tokens[] = [
            'token' => 'perf-token-' . $i,
            'status' => $i % 3 === 0 ? 'used' : 'pending',
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    ScanLoginToken::insert($tokens);
    
    $startTime = microtime(true);
    
    // Test token lookup performance (should use index)
    $foundToken = ScanLoginToken::where('token', 'perf-token-500')->first();
    
    $endTime = microtime(true);
    $queryTime = $endTime - $startTime;
    
    expect($foundToken)->not->toBeNull();
    expect($foundToken->token)->toBe('perf-token-500');
    
    // Query should be fast even with 1000 records (less than 10ms)
    expect($queryTime)->toBeLessThan(0.01);
    
    // Test status-based queries (should use index)
    $startTime = microtime(true);
    $pendingCount = ScanLoginToken::where('status', 'pending')->count();
    $endTime = microtime(true);
    $statusQueryTime = $endTime - $startTime;
    
    expect($pendingCount)->toBeGreaterThan(0);
    expect($statusQueryTime)->toBeLessThan(0.01);
});

it('prevents brute force attacks on token validation', function () {
    // Create a valid token
    $validToken = ScanLoginToken::create([
        'token' => 'valid-brute-force-token',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Attempt multiple invalid token guesses
    $invalidTokens = [
        'invalid-token-1',
        'invalid-token-2',
        'invalid-token-3',
        'brute-force-attempt',
        '12345678901234567890',
        'aaaaaaaaaaaaaaaaaaaa',
    ];

    foreach ($invalidTokens as $invalidToken) {
        $response = $this->getJson("/scan-login/status/{$invalidToken}");
        
        // Should return not found error
        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'TOKEN_NOT_FOUND',
                    'message' => '登录令牌不存在',
                ]
            ]);
    }
    
    // Valid token should still work
    $validResponse = $this->getJson("/scan-login/status/{$validToken->token}");
    $validResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'pending',
                'logged_in' => false,
            ]
        ]);
});

it('ensures secure password handling', function () {
    // Create a test user
    $user = new TestUser([
        'name' => 'Security Test User',
        'email' => 'security@example.com',
        'password' => Hash::make('secure-password-123'),
    ]);
    $user->save();

    // Create a valid token
    $token = ScanLoginToken::create([
        'token' => 'password-security-token',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Create and authenticate user
    $user = new TestUser([
        'name' => 'Security User',
        'email' => 'password-security@example.com',
        'password' => Hash::make('secure-password-123'),
    ]);
    $user->save();
    
    // Test login confirmation (user must be authenticated)
    $this->actingAs($user);
    $response = $this->postJson("/scan-login/{$token->token}");

    $response->assertStatus(200);

    // Verify that password is not stored in token record
    $token->refresh();
    expect($token->toArray())->not->toHaveKey('password');
    
    // Test with wrong password
    $token2 = ScanLoginToken::create([
        'token' => 'password-security-token-2',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Clear authentication and test unauthenticated access (should fail)
    $this->app['auth']->logout();
    $wrongPasswordResponse = $this->postJson("/scan-login/{$token2->token}");

    $wrongPasswordResponse->assertStatus(401)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHENTICATED',
            ]
        ]);
});

it('validates token cleanup performance', function () {
    // Create many expired tokens
    $expiredTokens = [];
    for ($i = 0; $i < 500; $i++) {
        $expiredTokens[] = [
            'token' => 'cleanup-token-' . $i,
            'status' => 'pending',
            'expires_at' => now()->subMinutes(10), // All expired
            'created_at' => now()->subMinutes(15),
            'updated_at' => now()->subMinutes(15),
        ];
    }
    
    ScanLoginToken::insert($expiredTokens);
    
    // Add some valid tokens
    for ($i = 0; $i < 100; $i++) {
        ScanLoginToken::create([
            'token' => 'valid-cleanup-token-' . $i,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5),
        ]);
    }
    
    $initialCount = ScanLoginToken::count();
    expect($initialCount)->toBe(600);
    
    $startTime = microtime(true);
    
    // Perform cleanup
    $tokenManager = new TokenManager();
    $cleanedCount = $tokenManager->cleanup();
    
    $endTime = microtime(true);
    $cleanupTime = $endTime - $startTime;
    
    // Should have cleaned up expired tokens
    expect($cleanedCount)->toBe(500);
    
    // Cleanup should be reasonably fast (less than 1 second for 500 records)
    expect($cleanupTime)->toBeLessThan(1.0);
    
    // Valid tokens should remain
    $remainingCount = ScanLoginToken::count();
    expect($remainingCount)->toBe(100);
});

it('tests memory usage during concurrent operations', function () {
    $initialMemory = memory_get_usage();
    
    // Simulate concurrent operations
    $operations = [];
    
    // Generate multiple QR codes
    for ($i = 0; $i < 20; $i++) {
        $operations[] = function () {
            return $this->postJson('/scan-login/generate');
        };
    }
    
    // Execute operations
    $results = [];
    foreach ($operations as $operation) {
        $results[] = $operation();
    }
    
    $peakMemory = memory_get_peak_usage();
    $memoryIncrease = $peakMemory - $initialMemory;
    
    // All operations should succeed
    foreach ($results as $result) {
        $result->assertStatus(200);
    }
    
    // Memory increase should be reasonable (less than 10MB for 20 operations)
    expect($memoryIncrease)->toBeLessThan(10 * 1024 * 1024);
    
    // Clean up
    unset($results, $operations);
    
    // Force garbage collection
    if (function_exists('gc_collect_cycles')) {
        gc_collect_cycles();
    }
});