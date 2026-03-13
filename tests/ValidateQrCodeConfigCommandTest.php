<?php

use Wuwx\LaravelScanLogin\Console\Commands\ValidateQrCodeConfigCommand;

beforeEach(function () {
    config([
        'scan-login.qr_code.size' => 200,
        'scan-login.qr_code.format' => 'svg',
        'scan-login.qr_code.margin' => 1,
        'scan-login.qr_code.error_correction' => 'high',
        'scan-login.qr_code.foreground_color' => '#000000',
        'scan-login.qr_code.background_color' => '#ffffff',
        'scan-login.qr_code.logo.enabled' => false,
        'scan-login.qr_code.logo.path' => null,
    ]);
});

it('validates correct configuration successfully', function () {
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsOutput('Validating QR code configuration...')
        ->expectsOutput('✓ QR code configuration is valid')
        ->assertExitCode(0);
});

it('detects invalid size configuration', function () {
    config(['scan-login.qr_code.size' => 50]); // Too small
    
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsOutput('✗ QR code configuration has errors:')
        ->assertExitCode(1);
});

it('detects invalid format configuration', function () {
    config(['scan-login.qr_code.format' => 'invalid']);
    
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsOutput('✗ QR code configuration has errors:')
        ->assertExitCode(1);
});

it('detects invalid margin configuration', function () {
    config(['scan-login.qr_code.margin' => 15]); // Too large
    
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsOutput('✗ QR code configuration has errors:')
        ->assertExitCode(1);
});

it('detects invalid error correction level', function () {
    config(['scan-login.qr_code.error_correction' => 'invalid']);
    
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsOutput('✗ QR code configuration has errors:')
        ->assertExitCode(1);
});

it('detects invalid foreground color', function () {
    config(['scan-login.qr_code.foreground_color' => 'not-a-color']);
    
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsOutput('✗ QR code configuration has errors:')
        ->assertExitCode(1);
});

it('detects invalid background color', function () {
    config(['scan-login.qr_code.background_color' => 'not-a-color']);
    
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsOutput('✗ QR code configuration has errors:')
        ->assertExitCode(1);
});

it('displays current configuration table', function () {
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsTable(
            ['Setting', 'Value'],
            [
                ['Format', 'svg'],
                ['Size', '200px'],
                ['Margin', '1'],
                ['Error Correction', 'high'],
                ['Foreground Color', '#000000'],
                ['Background Color', '#ffffff'],
                ['Logo Enabled', 'No'],
                ['Logo Path', 'Not set'],
            ]
        )
        ->assertExitCode(0);
});

it('displays php extensions status', function () {
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsOutput('PHP Extensions:')
        ->assertExitCode(0);
});

it('detects missing logo file when enabled', function () {
    config([
        'scan-login.qr_code.logo.enabled' => true,
        'scan-login.qr_code.logo.path' => '/nonexistent/logo.png',
    ]);
    
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsOutput('✗ QR code configuration has errors:')
        ->assertExitCode(1);
});

it('validates logo file exists when enabled', function () {
    // Create a temporary logo file
    $logoPath = sys_get_temp_dir() . '/test-logo.png';
    $image = imagecreate(100, 100);
    imagecolorallocate($image, 255, 0, 0);
    imagepng($image, $logoPath);
    imagedestroy($image);
    
    config([
        'scan-login.qr_code.logo.enabled' => true,
        'scan-login.qr_code.logo.path' => $logoPath,
    ]);
    
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsOutput('✓ QR code configuration is valid')
        ->assertExitCode(0);
    
    // Cleanup
    unlink($logoPath);
});

it('warns about png format without imagick', function () {
    if (extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick is available');
    }
    
    config(['scan-login.qr_code.format' => 'png']);
    
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsOutput('✗ QR code configuration has errors:')
        ->assertExitCode(1);
});

it('displays multiple errors when configuration has multiple issues', function () {
    config([
        'scan-login.qr_code.size' => 50, // Invalid
        'scan-login.qr_code.format' => 'invalid', // Invalid
        'scan-login.qr_code.margin' => 15, // Invalid
    ]);
    
    $this->artisan(ValidateQrCodeConfigCommand::class)
        ->expectsOutput('✗ QR code configuration has errors:')
        ->assertExitCode(1);
});
