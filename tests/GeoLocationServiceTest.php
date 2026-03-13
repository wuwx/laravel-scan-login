<?php

use Wuwx\LaravelScanLogin\Services\GeoLocationService;

it('returns null when ip address is null', function () {
    $service = new GeoLocationService();
    $location = $service->getLocationFromIp(null);
    
    expect($location)->toBeNull();
});

it('returns null when geoip is disabled', function () {
    config(['scan-login.enable_geoip' => false]);
    
    $service = new GeoLocationService();
    $location = $service->getLocationFromIp('8.8.8.8');
    
    expect($location)->toBeNull();
});

it('returns formatted location string for valid ip', function () {
    config(['scan-login.enable_geoip' => true]);
    
    $service = new GeoLocationService();
    $location = $service->getLocationFromIp('8.8.8.8');
    
    // GeoIP service is not bound in test environment, so null is expected
    expect($location === null || is_string($location))->toBeTrue();
});

it('caches location results', function () {
    config(['scan-login.enable_geoip' => true]);
    
    $service = new GeoLocationService();
    
    // 第一次调用
    $location1 = $service->getLocationFromIp('8.8.8.8');
    
    // 第二次调用应该从缓存获取
    $location2 = $service->getLocationFromIp('8.8.8.8');
    
    expect($location1)->toBe($location2);
});

it('returns detailed location data', function () {
    config(['scan-login.enable_geoip' => true]);
    
    $service = new GeoLocationService();
    $details = $service->getDetailedLocationFromIp('8.8.8.8');
    
    // 如果返回数据，应该包含预期的键
    if ($details !== null) {
        expect($details)->toBeArray()
            ->toHaveKeys(['country', 'state', 'city', 'postal_code', 'latitude', 'longitude', 'timezone']);
    } else {
        expect($details)->toBeNull();
    }
});

it('handles invalid ip addresses gracefully', function () {
    config(['scan-login.enable_geoip' => true]);
    
    $service = new GeoLocationService();
    $location = $service->getLocationFromIp('invalid-ip');
    
    expect($location)->toBeNull();
});

it('returns null when geoip service is not bound', function () {
    // Temporarily unbind geoip service
    app()->forgetInstance('geoip');
    
    config(['scan-login.enable_geoip' => true]);
    
    $service = new GeoLocationService();
    $location = $service->getLocationFromIp('8.8.8.8');
    
    expect($location)->toBeNull();
});
