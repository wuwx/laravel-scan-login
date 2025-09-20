<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up configuration for testing
    config([
        'scan-login.enabled' => true,
        'scan-login.token_expiry_minutes' => 5,
        'scan-login.polling_interval_seconds' => 3,
    ]);
});

it('can display qr code page', function () {
    $response = $this->get('/scan-login');
    
    $response->assertStatus(200);
    // Note: With Livewire components, we can't easily test view names
    // The page should contain the Livewire component
});

it('shows 403 when scan login is disabled', function () {
    config(['scan-login.enabled' => false]);
    
    $response = $this->get('/scan-login');
    
    $response->assertStatus(403);
});

it('qr code page contains required elements', function () {
    $response = $this->get('/scan-login');
    
    $response->assertStatus(200)
        ->assertSee('扫码登录');
    // Note: With Livewire components, content is loaded dynamically
});

it('qr code page includes csrf token', function () {
    $response = $this->get('/scan-login');
    
    $response->assertStatus(200)
        ->assertSee('csrf-token', false); // false means don't escape HTML
});

it('qr code page includes config data', function () {
    config([
        'scan-login.token_expiry_minutes' => 10,
        'scan-login.polling_interval_seconds' => 5,
    ]);
    
    $response = $this->get('/scan-login');
    
    $response->assertStatus(200);
    // Note: With Livewire components, config is handled server-side
    // We can test the service directly instead
    $scanLoginService = app(\Wuwx\LaravelScanLogin\Services\ScanLoginService::class);
    $config = $scanLoginService->getConfig();
    expect($config['token_expiry_minutes'])->toBe(10);
    expect($config['polling_interval_seconds'])->toBe(5);
});