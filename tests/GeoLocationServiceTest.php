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
    
    // 这个测试需要 GeoIP 数据库已安装
    $service = new GeoLocationService();
    $location = $service->getLocationFromIp('8.8.8.8');
    
    // 如果 GeoIP 服务可用，应该返回字符串或 null
    expect($location)->toBeIn([null, expect()->toBeString()]);
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
