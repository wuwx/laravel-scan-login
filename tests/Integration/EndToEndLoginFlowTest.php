<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Wuwx\LaravelScanLogin\Models\ScanLoginToken;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
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

it('completes full end to end login flow', function () {
    // Step 1: Create a test user
    $user = new TestUser([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
    $user->save();

    // Step 2: Desktop端 - Generate QR code
    $response = $this->postJson('/scan-login/generate');
        
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'token',
                'qr_code',
                'mobile_url',
                'expires_at'
            ]
        ]);

    $responseData = $response->json();
    expect($responseData['success'])->toBeTrue();
    
    $token = $responseData['data']['token'];
    $mobileUrl = $responseData['data']['mobile_url'];

    // Verify token was created in database
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'status' => 'pending',
    ]);

    // Step 3: Desktop端 - Initial status check (should be pending)
    $statusResponse = $this->getJson("/scan-login/status/{$token}");
    
    $statusResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'pending',
                'logged_in' => false,
            ]
        ]);

    // Step 4: Mobile端 - Access mobile login page
    $mobileResponse = $this->get("/scan-login/{$token}");
    
    $mobileResponse->assertStatus(200)
        ->assertViewIs('scan-login::mobile-login')
        ->assertViewHas('token', $token);

    // Step 5: Mobile端 - Submit login credentials
    $loginResponse = $this->postJson("/scan-login/{$token}", [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $loginResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'message' => '登录成功！桌面端将自动登录。',
            ]
        ]);

    // Verify token status was updated
    $this->assertDatabaseHas('scan_login_tokens', [
        'token' => $token,
        'status' => 'used',
        'user_id' => $user->id,
    ]);

    // Step 6: Desktop端 - Status check after mobile login (should be success)
    $finalStatusResponse = $this->getJson("/scan-login/status/{$token}");
    
    $finalStatusResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'used',
                'logged_in' => true,
                'user_id' => $user->id,
                'redirect_url' => config('scan-login.login_success_redirect', '/dashboard'),
            ]
        ]);

    // Step 7: Verify that subsequent status checks still work
    $subsequentStatusResponse = $this->getJson("/scan-login/status/{$token}");
    
    $subsequentStatusResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'used',
                'logged_in' => true,
            ]
        ]);
});

it('handles desktop and mobile interaction simulation', function () {
    // Create test user
    $user = new TestUser([
        'name' => 'Test User',
        'email' => 'mobile@example.com',
        'password' => Hash::make('secret123'),
    ]);
    $user->save();

    // Simulate desktop browser generating QR code
    $desktopSession = $this->withSession([]);
    $qrResponse = $desktopSession->postJson('/scan-login/generate');
    
    $qrResponse->assertStatus(200);
    $token = $qrResponse->json('data.token');

    // Simulate mobile browser accessing login page
    $mobileSession = $this->withSession([]);
    $mobilePageResponse = $mobileSession->get("/scan-login/{$token}");
    
    $mobilePageResponse->assertStatus(200);

    // Simulate desktop polling while mobile hasn't logged in yet
    $pollingResponse1 = $desktopSession->getJson("/scan-login/status/{$token}");
    $pollingResponse1->assertJson([
        'success' => true,
        'data' => ['status' => 'pending', 'logged_in' => false]
    ]);

    // Simulate mobile login
    $mobileLoginResponse = $mobileSession->postJson("/scan-login/{$token}", [
        'email' => 'mobile@example.com',
        'password' => 'secret123',
    ]);
    
    $mobileLoginResponse->assertStatus(200)
        ->assertJson(['success' => true]);

    // Simulate desktop polling after mobile login
    $pollingResponse2 = $desktopSession->getJson("/scan-login/status/{$token}");
    $pollingResponse2->assertJson([
        'success' => true,
        'data' => [
            'status' => 'used',
            'logged_in' => true,
            'user_id' => $user->id
        ]
    ]);
});

it('maintains proper token lifecycle throughout flow', function () {
    // Create test user
    $user = new TestUser([
        'name' => 'Lifecycle User',
        'email' => 'lifecycle@example.com',
        'password' => Hash::make('lifecycle123'),
    ]);
    $user->save();

    // Generate QR code and verify initial token state
    $response = $this->postJson('/scan-login/generate');
    $token = $response->json('data.token');

    $tokenModel = ScanLoginToken::where('token', $token)->first();
    expect($tokenModel)->not->toBeNull();
    expect($tokenModel->status)->toBe('pending');
    expect($tokenModel->user_id)->toBeNull();
    expect($tokenModel->used_at)->toBeNull();
    expect($tokenModel->isExpired())->toBeFalse();
    expect($tokenModel->isPending())->toBeTrue();

    // Process mobile login and verify token state changes
    $this->postJson("/scan-login/{$token}", [
        'email' => 'lifecycle@example.com',
        'password' => 'lifecycle123',
    ]);

    $tokenModel->refresh();
    expect($tokenModel->status)->toBe('used');
    expect($tokenModel->user_id)->toBe($user->id);
    expect($tokenModel->used_at)->not->toBeNull();
    expect($tokenModel->isPending())->toBeFalse();

    // Verify token cannot be reused
    $reuseResponse = $this->postJson("/scan-login/{$token}", [
        'email' => 'lifecycle@example.com',
        'password' => 'lifecycle123',
    ]);

    $reuseResponse->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => [
                'code' => 'TOKEN_ALREADY_USED',
            ]
        ]);
});

it('handles qr code generation with proper data structure', function () {
    $response = $this->postJson('/scan-login/generate');
    
    $response->assertStatus(200);
    $data = $response->json('data');

    // Verify all required fields are present
    expect($data)->toHaveKey('token');
    expect($data)->toHaveKey('qr_code');
    expect($data)->toHaveKey('mobile_url');
    expect($data)->toHaveKey('expires_at');

    // Verify token format
    expect($data['token'])->toBeString();
    expect(strlen($data['token']))->toBeGreaterThan(20); // Should be a substantial random string

    // Verify QR code is base64 encoded SVG
    expect($data['qr_code'])->toStartWith('data:image/svg+xml;base64,');

    // Verify mobile URL format
    expect($data['mobile_url'])->toContain("/scan-login/{$data['token']}");

    // Verify expires_at is a valid timestamp
    expect($data['expires_at'])->toBeString();
    expect(strtotime($data['expires_at']))->not->toBeFalse();
});

it('properly handles authentication flow integration', function () {
    // Create test user
    $user = new TestUser([
        'name' => 'Auth User',
        'email' => 'auth@example.com',
        'password' => Hash::make('authpass'),
    ]);
    $user->save();

    // Generate token
    $response = $this->postJson('/scan-login/generate');
    $token = $response->json('data.token');

    // Ensure no user is authenticated initially
    expect(Auth::check())->toBeFalse();

    // Process login
    $loginResponse = $this->postJson("/scan-login/{$token}", [
        'email' => 'auth@example.com',
        'password' => 'authpass',
    ]);

    $loginResponse->assertStatus(200);

    // Check that the token has the correct user associated
    $tokenModel = ScanLoginToken::where('token', $token)->first();
    expect($tokenModel->user_id)->toBe($user->id);

    // Verify status endpoint returns correct user information
    $statusResponse = $this->getJson("/scan-login/status/{$token}");
    $statusResponse->assertJson([
        'success' => true,
        'data' => [
            'status' => 'used',
            'logged_in' => true,
            'user_id' => $user->id,
        ]
    ]);
});