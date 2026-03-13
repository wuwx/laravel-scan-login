<?php

namespace Wuwx\LaravelScanLogin\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeoLocationService
{
    /**
     * Get formatted location string from IP address.
     */
    public function getLocationFromIp(?string $ipAddress): ?string
    {
        if (!$ipAddress || !config('scan-login.enable_geoip', true)) {
            return null;
        }

        // 检查是否安装了 GeoIP
        if (!app()->bound('geoip')) {
            Log::debug('GeoIP service not available. Install torann/geoip package.');
            return null;
        }

        // 使用缓存避免重复查询
        $cacheKey = 'scan_login_geoip_' . md5($ipAddress);
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($ipAddress) {
            try {
                $geoip = app('geoip')->getLocation($ipAddress);
                
                if (!$geoip || !$geoip->country) {
                    return null;
                }
                
                $locationParts = [];
                
                // 添加国家
                if ($geoip->country) {
                    $locationParts[] = $geoip->country;
                }
                
                // 添加省份/州
                if (!empty($geoip->state_name)) {
                    $locationParts[] = $geoip->state_name;
                }
                
                // 添加城市
                if (!empty($geoip->city)) {
                    $locationParts[] = $geoip->city;
                }
                
                return !empty($locationParts) ? implode(' · ', $locationParts) : null;
                
            } catch (\Throwable $e) {
                Log::debug('GeoIP lookup failed: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get detailed location data from IP address.
     */
    public function getDetailedLocationFromIp(?string $ipAddress): ?array
    {
        if (!$ipAddress || !config('scan-login.enable_geoip', true)) {
            return null;
        }

        if (!app()->bound('geoip')) {
            return null;
        }

        try {
            $geoip = app('geoip')->getLocation($ipAddress);
            
            if (!$geoip) {
                return null;
            }
            
            return [
                'country' => $geoip->country ?? null,
                'state' => $geoip->state_name ?? null,
                'city' => $geoip->city ?? null,
                'postal_code' => $geoip->postal_code ?? null,
                'latitude' => $geoip->lat ?? null,
                'longitude' => $geoip->lon ?? null,
                'timezone' => $geoip->timezone ?? null,
            ];
            
        } catch (\Throwable $e) {
            Log::debug('GeoIP lookup failed: ' . $e->getMessage());
            return null;
        }
    }
}
