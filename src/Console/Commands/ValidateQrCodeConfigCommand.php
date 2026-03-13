<?php

namespace Wuwx\LaravelScanLogin\Console\Commands;

use Illuminate\Console\Command;
use Wuwx\LaravelScanLogin\Services\QrCodeService;

class ValidateQrCodeConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan-login:validate-qr-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate QR code configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Validating QR code configuration...');
        $this->newLine();

        $service = app(QrCodeService::class);
        $errors = $service->validateConfig();

        if (empty($errors)) {
            $this->info('✓ QR code configuration is valid');
            $this->displayCurrentConfig();
            return self::SUCCESS;
        }

        $this->error('✗ QR code configuration has errors:');
        $this->newLine();

        foreach ($errors as $error) {
            $this->error('  • ' . $error);
        }

        $this->newLine();
        $this->displayCurrentConfig();

        return self::FAILURE;
    }

    /**
     * Display current configuration.
     */
    protected function displayCurrentConfig(): void
    {
        $this->newLine();
        $this->info('Current Configuration:');
        
        $this->table(
            ['Setting', 'Value'],
            [
                ['Format', config('scan-login.qr_code.format')],
                ['Size', config('scan-login.qr_code.size') . 'px'],
                ['Margin', config('scan-login.qr_code.margin')],
                ['Error Correction', config('scan-login.qr_code.error_correction')],
                ['Foreground Color', config('scan-login.qr_code.foreground_color')],
                ['Background Color', config('scan-login.qr_code.background_color')],
                ['Logo Enabled', config('scan-login.qr_code.logo.enabled') ? 'Yes' : 'No'],
                ['Logo Path', config('scan-login.qr_code.logo.path') ?: 'Not set'],
            ]
        );

        // Check extensions
        $this->newLine();
        $this->info('PHP Extensions:');
        $this->table(
            ['Extension', 'Status'],
            [
                ['Imagick', extension_loaded('imagick') ? '✓ Installed' : '✗ Not installed'],
                ['GD', extension_loaded('gd') ? '✓ Installed' : '✗ Not installed'],
            ]
        );
    }
}
