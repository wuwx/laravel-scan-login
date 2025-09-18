<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Illuminate\Foundation\Auth\User;
use Illuminate\Contracts\Auth\UserProvider;
use Mockery;

class TestUser extends User
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
}

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up configuration for testing
    config([
        'scan-login.enabled' => true,
        'scan-login.token_expiry_minutes' => 5,
        'scan-login.polling_interval_seconds' => 1,
    ]);

    // Create users table for testing
    $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });

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
        'auth.passwords.users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ]);

    // Mock the UserProvider for the ScanLoginService
    $userProvider = Mockery::mock(UserProvider::class);
    $this->app->instance(UserProvider::class, $userProvider);
    
    // Set up the mock to return users when needed
    $userProvider->shouldReceive('retrieveByCredentials')
        ->andReturnUsing(function ($credentials) {
            return TestUser::where('email', $credentials['email'])->first();
        });
    
    $userProvider->shouldReceive('validateCredentials')
        ->andReturnUsing(function ($user, $credentials) {
            return $user && Hash::check($credentials['password'], $user->password);
        });
});

it('handles expired token scenario', function () {
    // Create an expired token directly in the database
    $expiredToken = ScanLoginToken::create([
        'token' => 'expired-token-123',
        'status' => 'pending',
        'expires_at' => now()->subMinutes(10), // Expired 10 minutes ago
    ]);

    // Try to access mobile login page with expired token
    $response = $this->get("/scan-login/{$expiredToken->token}");
    
    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'TOKEN_EXPIRED',
            ]
        ]);

    // Try to submit login with expired token
    $loginResponse = $this->postJson("/scan-login/{$expiredToken->token}", [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $loginResponse->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'TOKEN_EXPIRED',
            ]
        ]);

    // Check status of expired token
    $statusResponse = $this->getJson("/scan-login/status/{$expiredToken->token}");
    
    $statusResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'expired',
                'logged_in' => false,
            ]
        ]);
});

it('handles invalid credentials scenario', function () {
    // Create a valid token
    $token = ScanLoginToken::create([
        'token' => 'valid-token-123',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Create a test user
    $user = new TestUser([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('correct-password'),
    ]);
    $user->save();

    // Try to login with wrong password
    $response = $this->postJson("/scan-login/{$token->token}", [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'INVALID_CREDENTIALS',
                'message' => '用户名或密码错误',
            ]
        ]);

    // Verify token is still pending
    $token->refresh();
    expect($token->status)->toBe('pending');
    expect($token->user_id)->toBeNull();

    // Try to login with non-existent user
    $response2 = $this->postJson("/scan-login/{$token->token}", [
        'email' => 'nonexistent@example.com',
        'password' => 'any-password',
    ]);

    $response2->assertStatus(401)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'INVALID_CREDENTIALS',
                'message' => '用户名或密码错误',
            ]
        ]);
});

it('handles invalid token format scenario', function () {
    // Try to access with completely invalid token
    $response = $this->get('/scan-login/invalid-token-format');
    
    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'TOKEN_NOT_FOUND',
            ]
        ]);

    // Try to check status of non-existent token
    $statusResponse = $this->getJson('/scan-login/status/non-existent-token');
    
    $statusResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'not_found',
                'logged_in' => false,
            ]
        ]);
});

it('handles already used token scenario', function () {
    // Create a test user
    $user = new TestUser([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
    $user->save();

    // Create a token that's already been used
    $usedToken = ScanLoginToken::create([
        'token' => 'used-token-123',
        'status' => 'used',
        'user_id' => $user->id,
        'expires_at' => now()->addMinutes(5),
        'used_at' => now()->subMinutes(1),
    ]);

    // Try to access mobile login page with used token
    $response = $this->get("/scan-login/{$usedToken->token}");
    
    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'TOKEN_ALREADY_USED',
            ]
        ]);

    // Try to submit login with used token
    $loginResponse = $this->postJson("/scan-login/{$usedToken->token}", [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $loginResponse->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'TOKEN_ALREADY_USED',
            ]
        ]);
});

it('handles missing required fields scenario', function () {
    // Create a valid token
    $token = ScanLoginToken::create([
        'token' => 'valid-token-123',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Try to submit login without email
    $response = $this->postJson("/scan-login/{$token->token}", [
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    // Try to submit login without password
    $response2 = $this->postJson("/scan-login/{$token->token}", [
        'email' => 'test@example.com',
    ]);

    $response2->assertStatus(422)
        ->assertJsonValidationErrors(['password']);

    // Try to submit login with invalid email format
    $response3 = $this->postJson("/scan-login/{$token->token}", [
        'email' => 'invalid-email-format',
        'password' => 'password123',
    ]);

    $response3->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('handles disabled feature scenario', function () {
    // Disable the scan login feature
    config(['scan-login.enabled' => false]);

    // Try to generate QR code when feature is disabled
    $response = $this->postJson('/scan-login/generate');
    
    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'FEATURE_DISABLED',
                'message' => '扫码登录功能已禁用',
            ]
        ]);

    // Create a token for testing other endpoints
    $token = ScanLoginToken::create([
        'token' => 'test-token-123',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Try to check status when feature is disabled
    $statusResponse = $this->getJson("/scan-login/status/{$token->token}");
    
    $statusResponse->assertStatus(403)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'FEATURE_DISABLED',
                'message' => '扫码登录功能已禁用',
            ]
        ]);

    // Try to process mobile login when feature is disabled
    $loginResponse = $this->postJson("/scan-login/{$token->token}", [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $loginResponse->assertStatus(403)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'FEATURE_DISABLED',
                'message' => '扫码登录功能已禁用',
            ]
        ]);
});

it('handles network timeout and retry scenarios', function () {
    // Create a valid token
    $token = ScanLoginToken::create([
        'token' => 'valid-token-123',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Simulate multiple rapid status checks (like polling)
    $responses = [];
    for ($i = 0; $i < 5; $i++) {
        $responses[] = $this->getJson("/scan-login/status/{$token->token}");
    }

    // All should return pending status
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
});

it('handles concurrent login attempts scenario', function () {
    // Create a test user
    $user = new TestUser([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
    $user->save();

    // Create a valid token
    $token = ScanLoginToken::create([
        'token' => 'concurrent-token-123',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(5),
    ]);

    // Simulate first login attempt
    $response1 = $this->postJson("/scan-login/{$token->token}", [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    // First attempt should succeed
    $response1->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'message' => '登录成功！桌面端将自动登录。',
            ]
        ]);

    // Simulate second concurrent login attempt
    $response2 = $this->postJson("/scan-login/{$token->token}", [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    // Second attempt should fail because token is already used
    $response2->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'TOKEN_ALREADY_USED',
            ]
        ]);
});

it('provides proper error feedback for user experience', function () {
    // Test that error messages are user-friendly and informative
    
    // Create expired token
    $expiredToken = ScanLoginToken::create([
        'token' => 'expired-token-123',
        'status' => 'pending',
        'expires_at' => now()->subMinutes(10),
    ]);

    $response = $this->get("/scan-login/{$expiredToken->token}");
    
    $response->assertStatus(400);
    $errorData = $response->json();
    
    expect($errorData)->toHaveKey('success');
    expect($errorData)->toHaveKey('error');
    expect($errorData['error'])->toHaveKey('code');
    expect($errorData['error'])->toHaveKey('message');
    expect($errorData['success'])->toBeFalse();
    expect($errorData['error']['code'])->toBe('TOKEN_EXPIRED');
    expect($errorData['error']['message'])->toBeString();
    expect(strlen($errorData['error']['message']))->toBeGreaterThan(0);
});