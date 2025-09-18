<?php

use Wuwx\LaravelScanLogin\Services\QrCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Register the route for testing
    Route::get('/scan-login/{token}', function ($token) {
        return response()->json(['token' => $token]);
    })->name('scan-login.mobile-login');
    
    $this->qrCodeGenerator = new QrCodeGenerator();
});

it('can generate login url', function () {
    $token = 'test-token-123';
    
    $url = $this->qrCodeGenerator->generateLoginUrl($token);
    
    expect($url)->toContain('/scan-login/' . $token);
    expect($url)->toStartWith('http');
});

it('can generate qr code svg', function () {
    $token = 'test-token-123';
    
    $qrCode = $this->qrCodeGenerator->generate($token);
    
    expect($qrCode)->toBeString();
    expect($qrCode)->toContain('<svg');
    expect($qrCode)->toContain('</svg>');
});

it('uses configured qr code size', function () {
    config(['scan-login.qr_code_size' => 300]);
    $token = 'test-token-123';
    
    $qrCode = $this->qrCodeGenerator->generate($token);
    
    expect($qrCode)->toContain('width="300"');
    expect($qrCode)->toContain('height="300"');
});

it('can generate png data url', function () {
    $token = 'test-token-123';
    
    $dataUrl = $this->qrCodeGenerator->generatePng($token);
    
    // Should be either PNG or SVG fallback
    expect($dataUrl)->toMatch('/^data:image\/(png|svg\+xml);base64,/');
    expect(strlen($dataUrl))->toBeGreaterThan(100); // Should have substantial base64 content
});

it('can generate qr code with custom options', function () {
    $token = 'test-token-123';
    $options = [
        'size' => 150,
        'format' => 'svg',
        'margin' => 2,
        'errorCorrection' => 'H',
    ];
    
    $qrCode = $this->qrCodeGenerator->generateWithOptions($token, $options);
    
    expect($qrCode)->toBeString();
    expect($qrCode)->toContain('<svg');
    expect($qrCode)->toContain('width="150"');
    expect($qrCode)->toContain('height="150"');
});

it('can generate png with custom options', function () {
    $token = 'test-token-123';
    $options = [
        'size' => 100,
        'format' => 'png',
    ];
    
    $qrCode = $this->qrCodeGenerator->generateWithOptions($token, $options);
    
    expect($qrCode)->toBeString();
    expect(strlen($qrCode))->toBeGreaterThan(100); // Should have binary data or SVG fallback
});

it('can validate scan login url', function () {
    $validUrl = $this->qrCodeGenerator->generateLoginUrl('test-token');
    $invalidUrl = 'https://example.com/some-other-path';
    
    expect($this->qrCodeGenerator->isValidScanLoginUrl($validUrl))->toBeTrue();
    expect($this->qrCodeGenerator->isValidScanLoginUrl($invalidUrl))->toBeFalse();
});

it('can extract token from url', function () {
    $token = 'test-token-123';
    $url = $this->qrCodeGenerator->generateLoginUrl($token);
    
    $extractedToken = $this->qrCodeGenerator->extractTokenFromUrl($url);
    
    expect($extractedToken)->toBe($token);
});

it('returns null for invalid url when extracting token', function () {
    $invalidUrl = 'https://example.com/some-other-path';
    
    $extractedToken = $this->qrCodeGenerator->extractTokenFromUrl($invalidUrl);
    
    expect($extractedToken)->toBeNull();
});

it('returns null for malformed scan login url', function () {
    $malformedUrl = url('/scan-login/');
    
    $extractedToken = $this->qrCodeGenerator->extractTokenFromUrl($malformedUrl);
    
    expect($extractedToken)->toBeNull();
});

it('uses default size when not configured', function () {
    config(['scan-login.qr_code_size' => null]);
    $token = 'test-token-123';
    
    $qrCode = $this->qrCodeGenerator->generate($token);
    
    expect($qrCode)->toContain('width="200"'); // Default size
    expect($qrCode)->toContain('height="200"');
});

it('can generate qr code with background color', function () {
    $token = 'test-token-123';
    $options = [
        'backgroundColor' => [255, 255, 255], // White background
        'format' => 'svg',
    ];
    
    $qrCode = $this->qrCodeGenerator->generateWithOptions($token, $options);
    
    expect($qrCode)->toBeString();
    expect($qrCode)->toContain('<svg');
});

it('can generate qr code with foreground color', function () {
    $token = 'test-token-123';
    $options = [
        'color' => [0, 0, 0], // Black foreground
        'format' => 'svg',
    ];
    
    $qrCode = $this->qrCodeGenerator->generateWithOptions($token, $options);
    
    expect($qrCode)->toBeString();
    expect($qrCode)->toContain('<svg');
});