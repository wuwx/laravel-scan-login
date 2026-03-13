<?php

use Wuwx\LaravelScanLogin\Services\QrCodeService;

beforeEach(function () {
    config([
        'scan-login.qr_code.size' => 200,
        'scan-login.qr_code.format' => 'svg',
        'scan-login.qr_code.margin' => 1,
        'scan-login.qr_code.error_correction' => 'high',
        'scan-login.qr_code.foreground_color' => '#000000',
        'scan-login.qr_code.background_color' => '#ffffff',
    ]);
});

it('generates svg qr code', function () {
    $service = new QrCodeService();
    $qrCode = $service->generate('https://example.com');
    
    expect($qrCode)->toBeString()
        ->and($qrCode)->toContain('<?xml')
        ->and($qrCode)->toContain('svg');
});

it('generates png qr code when format is png', function () {
    if (!extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick extension not available');
    }
    
    config(['scan-login.qr_code.format' => 'png']);
    
    $service = new QrCodeService();
    $qrCode = $service->generate('https://example.com');
    
    expect($qrCode)->toBeString()
        ->and($qrCode)->toStartWith('data:image/png;base64,');
});

it('validates configuration correctly', function () {
    config([
        'scan-login.qr_code.size' => 200,
        'scan-login.qr_code.margin' => 1,
        'scan-login.qr_code.format' => 'svg',
    ]);
    
    $service = new QrCodeService();
    $errors = $service->validateConfig();
    
    expect($errors)->toBeArray()->toBeEmpty();
});

it('detects invalid size', function () {
    config(['scan-login.qr_code.size' => 50]); // Too small
    
    $service = new QrCodeService();
    $errors = $service->validateConfig();
    
    expect($errors)->toBeArray()
        ->and($errors)->toHaveCount(1)
        ->and($errors[0])->toContain('size');
});

it('detects invalid margin', function () {
    config(['scan-login.qr_code.margin' => 15]); // Too large
    
    $service = new QrCodeService();
    $errors = $service->validateConfig();
    
    expect($errors)->toBeArray()
        ->and($errors)->not->toBeEmpty();
});

it('detects invalid format', function () {
    config(['scan-login.qr_code.format' => 'invalid']);
    
    $service = new QrCodeService();
    $errors = $service->validateConfig();
    
    expect($errors)->toBeArray()
        ->and($errors)->not->toBeEmpty();
});

it('uses custom colors', function () {
    config([
        'scan-login.qr_code.foreground_color' => '#FF0000',
        'scan-login.qr_code.background_color' => '#00FF00',
    ]);
    
    $service = new QrCodeService();
    $qrCode = $service->generate('https://example.com');
    
    expect($qrCode)->toBeString();
});

it('handles different error correction levels', function () {
    $levels = ['low', 'medium', 'quartile', 'high'];
    
    foreach ($levels as $level) {
        config(['scan-login.qr_code.error_correction' => $level]);
        
        $service = new QrCodeService();
        $qrCode = $service->generate('https://example.com');
        
        expect($qrCode)->toBeString();
    }
});

it('throws exception for png without imagick', function () {
    if (extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick is available');
    }
    
    config(['scan-login.qr_code.format' => 'png']);
    
    $service = new QrCodeService();
    
    expect(fn() => $service->generate('https://example.com'))
        ->toThrow(RuntimeException::class);
});

it('generates qr code with logo', function () {
    if (!extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick extension not available');
    }
    
    // Create a test logo
    $logoPath = sys_get_temp_dir() . '/test-logo.png';
    $image = imagecreate(100, 100);
    imagecolorallocate($image, 255, 0, 0);
    imagepng($image, $logoPath);
    imagedestroy($image);
    
    $service = new QrCodeService();
    $qrCode = $service->generateWithLogo('https://example.com', $logoPath);
    
    expect($qrCode)->toBeString()
        ->and($qrCode)->toStartWith('data:image/png;base64,');
    
    // Cleanup
    unlink($logoPath);
});

it('throws exception for missing logo file', function () {
    if (!extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick extension not available');
    }
    
    $service = new QrCodeService();
    
    expect(fn() => $service->generateWithLogo('https://example.com', '/nonexistent/logo.png'))
        ->toThrow(InvalidArgumentException::class);
});
