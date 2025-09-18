<?php

namespace Wuwx\LaravelScanLogin\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeGenerator
{
    /**
     * Generate a QR code for the given token.
     */
    public function generate(string $token): string
    {
        $loginUrl = $this->generateLoginUrl($token);
        $size = config('scan-login.qr_code_size', 200);
        
        // Ensure size is an integer
        $size = is_numeric($size) ? (int) $size : 200;
        
        return QrCode::size($size)
            ->format('svg')
            ->generate($loginUrl);
    }

    /**
     * Generate the login URL for the given token.
     */
    public function generateLoginUrl(string $token): string
    {
        return url(route('scan-login.mobile-login', ['token' => $token]));
    }

    /**
     * Generate a QR code as PNG data URL.
     */
    public function generatePng(string $token): string
    {
        $loginUrl = $this->generateLoginUrl($token);
        $size = config('scan-login.qr_code_size', 200);
        
        // Use SVG format as fallback if imagick is not available
        try {
            $qrCode = QrCode::size($size)
                ->format('png')
                ->generate($loginUrl);
                
            return 'data:image/png;base64,' . base64_encode($qrCode);
        } catch (\RuntimeException $e) {
            // Fallback to SVG if PNG is not available
            $svgCode = QrCode::size($size)
                ->format('svg')
                ->generate($loginUrl);
                
            return 'data:image/svg+xml;base64,' . base64_encode($svgCode);
        }
    }

    /**
     * Generate a QR code with custom options.
     */
    public function generateWithOptions(string $token, array $options = []): string
    {
        $loginUrl = $this->generateLoginUrl($token);
        
        $size = $options['size'] ?? config('scan-login.qr_code_size', 200);
        $format = $options['format'] ?? 'svg';
        $margin = $options['margin'] ?? 0;
        $errorCorrection = $options['errorCorrection'] ?? 'M';
        
        // Handle PNG format with fallback
        if ($format === 'png') {
            try {
                $qrCode = QrCode::size($size)
                    ->format($format)
                    ->margin($margin)
                    ->errorCorrection($errorCorrection);
                    
                // Add background color if specified
                if (isset($options['backgroundColor'])) {
                    $qrCode->backgroundColor(...$options['backgroundColor']);
                }
                
                // Add foreground color if specified
                if (isset($options['color'])) {
                    $qrCode->color(...$options['color']);
                }
                
                return $qrCode->generate($loginUrl);
            } catch (\RuntimeException $e) {
                // Fallback to SVG if PNG is not available
                $format = 'svg';
            }
        }
        
        $qrCode = QrCode::size($size)
            ->format($format)
            ->margin($margin)
            ->errorCorrection($errorCorrection);
            
        // Add background color if specified
        if (isset($options['backgroundColor'])) {
            $qrCode->backgroundColor(...$options['backgroundColor']);
        }
        
        // Add foreground color if specified
        if (isset($options['color'])) {
            $qrCode->color(...$options['color']);
        }
        
        return $qrCode->generate($loginUrl);
    }

    /**
     * Validate if a URL belongs to this application's scan login.
     */
    public function isValidScanLoginUrl(string $url): bool
    {
        try {
            $baseUrl = url(route('scan-login.mobile-login', ['token' => 'dummy']));
            $baseUrlWithoutToken = str_replace('/dummy', '', $baseUrl);
            
            return str_starts_with($url, $baseUrlWithoutToken);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract token from a scan login URL.
     */
    public function extractTokenFromUrl(string $url): ?string
    {
        if (!$this->isValidScanLoginUrl($url)) {
            return null;
        }
        
        $pattern = '/\/scan-login\/([a-zA-Z0-9\-_]+)(?:\?.*)?$/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}