<?php

namespace Wuwx\LaravelScanLogin\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\RendererStyle\Fill;

class QrCodeService
{
    /**
     * Generate QR code.
     */
    public function generate(string $content): string
    {
        $format = config('scan-login.qr_code.format', 'svg');
        
        return match ($format) {
            'svg' => $this->generateSvg($content),
            'png' => $this->generatePng($content),
            default => $this->generateSvg($content),
        };
    }

    /**
     * Generate SVG QR code.
     */
    protected function generateSvg(string $content): string
    {
        $size = config('scan-login.qr_code.size', 200);
        $margin = config('scan-login.qr_code.margin', 1);
        $errorCorrection = $this->getErrorCorrectionLevel();
        
        $foregroundColor = $this->parseColor(
            config('scan-login.qr_code.foreground_color', '#000000')
        );
        
        $backgroundColor = $this->parseColor(
            config('scan-login.qr_code.background_color', '#ffffff')
        );

        $renderer = new ImageRenderer(
            new RendererStyle(
                $size,
                $margin,
                null,
                null,
                Fill::uniformColor(
                    $backgroundColor,
                    $foregroundColor
                )
            ),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        
        return $writer->writeString($content, 'utf-8', $errorCorrection);
    }

    /**
     * Generate PNG QR code.
     */
    protected function generatePng(string $content): string
    {
        if (!extension_loaded('imagick')) {
            throw new \RuntimeException('Imagick extension is required for PNG QR codes');
        }

        $size = config('scan-login.qr_code.size', 200);
        $margin = config('scan-login.qr_code.margin', 1);
        $errorCorrection = $this->getErrorCorrectionLevel();
        
        $foregroundColor = $this->parseColor(
            config('scan-login.qr_code.foreground_color', '#000000')
        );
        
        $backgroundColor = $this->parseColor(
            config('scan-login.qr_code.background_color', '#ffffff')
        );

        $renderer = new ImageRenderer(
            new RendererStyle(
                $size,
                $margin,
                null,
                null,
                Fill::withForegroundColor(
                    $backgroundColor,
                    $foregroundColor
                )
            ),
            new ImagickImageBackEnd()
        );

        $writer = new Writer($renderer);
        $pngData = $writer->writeString($content, 'utf-8', $errorCorrection);
        
        // Convert to base64 data URL for easy embedding
        return 'data:image/png;base64,' . base64_encode($pngData);
    }

    /**
     * Get error correction level.
     */
    protected function getErrorCorrectionLevel(): \BaconQrCode\Common\ErrorCorrectionLevel
    {
        $level = config('scan-login.qr_code.error_correction', 'high');
        
        return match (strtolower($level)) {
            'low' => \BaconQrCode\Common\ErrorCorrectionLevel::L(),
            'medium' => \BaconQrCode\Common\ErrorCorrectionLevel::M(),
            'quartile' => \BaconQrCode\Common\ErrorCorrectionLevel::Q(),
            'high' => \BaconQrCode\Common\ErrorCorrectionLevel::H(),
            default => \BaconQrCode\Common\ErrorCorrectionLevel::H(),
        };
    }

    /**
     * Parse color string to RGB object.
     */
    protected function parseColor(string $color): Rgb
    {
        // Remove # if present
        $color = ltrim($color, '#');
        
        // Convert hex to RGB
        if (strlen($color) === 6) {
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));
        } elseif (strlen($color) === 3) {
            $r = hexdec(str_repeat(substr($color, 0, 1), 2));
            $g = hexdec(str_repeat(substr($color, 1, 1), 2));
            $b = hexdec(str_repeat(substr($color, 2, 1), 2));
        } else {
            // Default to black
            $r = $g = $b = 0;
        }
        
        return new Rgb($r, $g, $b);
    }

    /**
     * Generate QR code with logo.
     */
    public function generateWithLogo(string $content, string $logoPath): string
    {
        if (!extension_loaded('imagick')) {
            throw new \RuntimeException('Imagick extension is required for QR codes with logo');
        }

        if (!file_exists($logoPath)) {
            throw new \InvalidArgumentException("Logo file not found: {$logoPath}");
        }

        // Generate base QR code
        $qrCode = $this->generatePng($content);
        
        // Extract base64 data
        $qrCodeData = base64_decode(explode(',', $qrCode)[1]);
        
        // Create Imagick objects
        $qrImage = new \Imagick();
        $qrImage->readImageBlob($qrCodeData);
        
        $logo = new \Imagick($logoPath);
        
        // Calculate logo size (20% of QR code size)
        $qrWidth = $qrImage->getImageWidth();
        $qrHeight = $qrImage->getImageHeight();
        $logoSize = min($qrWidth, $qrHeight) * 0.2;
        
        // Resize logo
        $logo->scaleImage($logoSize, $logoSize, true);
        
        // Add white background to logo
        $logoWithBg = new \Imagick();
        $logoWithBg->newImage(
            $logoSize * 1.2,
            $logoSize * 1.2,
            new \ImagickPixel('white')
        );
        $logoWithBg->compositeImage(
            $logo,
            \Imagick::COMPOSITE_OVER,
            ($logoWithBg->getImageWidth() - $logo->getImageWidth()) / 2,
            ($logoWithBg->getImageHeight() - $logo->getImageHeight()) / 2
        );
        
        // Overlay logo on QR code
        $qrImage->compositeImage(
            $logoWithBg,
            \Imagick::COMPOSITE_OVER,
            ($qrWidth - $logoWithBg->getImageWidth()) / 2,
            ($qrHeight - $logoWithBg->getImageHeight()) / 2
        );
        
        // Convert to base64
        $qrImage->setImageFormat('png');
        return 'data:image/png;base64,' . base64_encode($qrImage->getImageBlob());
    }

    /**
     * Validate QR code configuration.
     */
    public function validateConfig(): array
    {
        $errors = [];
        
        // Validate size
        $size = config('scan-login.qr_code.size');
        if ($size < 100 || $size > 1000) {
            $errors[] = 'QR code size must be between 100 and 1000 pixels';
        }
        
        // Validate margin
        $margin = config('scan-login.qr_code.margin');
        if ($margin < 0 || $margin > 10) {
            $errors[] = 'QR code margin must be between 0 and 10';
        }
        
        // Validate format
        $format = config('scan-login.qr_code.format');
        if (!in_array($format, ['svg', 'png'])) {
            $errors[] = 'QR code format must be either "svg" or "png"';
        }
        
        // Validate PNG format requirements
        if ($format === 'png' && !extension_loaded('imagick')) {
            $errors[] = 'Imagick extension is required for PNG format';
        }
        
        // Validate error correction level
        $errorCorrection = config('scan-login.qr_code.error_correction');
        if (!in_array(strtolower($errorCorrection), ['low', 'medium', 'quartile', 'high'])) {
            $errors[] = 'Error correction level must be one of: low, medium, quartile, high';
        }
        
        // Validate foreground color
        $foregroundColor = config('scan-login.qr_code.foreground_color');
        if (!$this->isValidColor($foregroundColor)) {
            $errors[] = 'Foreground color must be a valid hex color (e.g., #000000 or #000)';
        }
        
        // Validate background color
        $backgroundColor = config('scan-login.qr_code.background_color');
        if (!$this->isValidColor($backgroundColor)) {
            $errors[] = 'Background color must be a valid hex color (e.g., #ffffff or #fff)';
        }
        
        // Validate logo configuration
        $logoEnabled = config('scan-login.qr_code.logo.enabled');
        if ($logoEnabled) {
            $logoPath = config('scan-login.qr_code.logo.path');
            if (empty($logoPath)) {
                $errors[] = 'Logo path must be specified when logo is enabled';
            } elseif (!file_exists($logoPath)) {
                $errors[] = "Logo file not found: {$logoPath}";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate if a string is a valid hex color.
     */
    protected function isValidColor(string $color): bool
    {
        // Remove # if present
        $color = ltrim($color, '#');
        
        // Check if it's a valid 3 or 6 character hex color
        return preg_match('/^[0-9A-Fa-f]{3}$|^[0-9A-Fa-f]{6}$/', $color) === 1;
    }
}
