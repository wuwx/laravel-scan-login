<?php

use Illuminate\Support\Facades\Cache;
use Wuwx\LaravelScanLogin\Services\RateLimitService;
use Wuwx\LaravelScanLogin\Services\ScanLoginTokenService;

beforeEach(function () {
    Cache::flush();
    config(['scan-login.rate_limit.enabled' => true]);
});

it('allows requests within rate limit', function () {
    config([
        'scan-login.rate_limit.max_attempts' => 5,
        'scan-login.rate_limit.decay_minutes' => 1,
    ]);

    $service = app(RateLimitService::class);
    $request = request();

    // 前 5 次请求应该被允许
    for ($i = 0; $i < 5; $i++) {
        expect($service->shouldLimit($request, 'test_action'))->toBeFalse();
    }
});

it('blocks requests exceeding rate limit', function () {
    config([
        'scan-login.rate_limit.max_attempts' => 3,
        'scan-login.rate_limit.decay_minutes' => 1,
    ]);

    $service = app(RateLimitService::class);
    $request = request();

    // 前 3 次请求被允许
    for ($i = 0; $i < 3; $i++) {
        $service->shouldLimit($request, 'test_action');
    }

    // 第 4 次请求应该被阻止
    expect($service->shouldLimit($request, 'test_action'))->toBeTrue();
});

it('returns correct remaining attempts', function () {
    config([
        'scan-login.rate_limit.max_attempts' => 5,
        'scan-login.rate_limit.decay_minutes' => 1,
    ]);

    $service = app(RateLimitService::class);
    $request = request();

    expect($service->remainingAttempts($request, 'test_action'))->toBe(5);

    $service->shouldLimit($request, 'test_action');
    expect($service->remainingAttempts($request, 'test_action'))->toBe(4);

    $service->shouldLimit($request, 'test_action');
    expect($service->remainingAttempts($request, 'test_action'))->toBe(3);
});

it('can clear rate limit', function () {
    config([
        'scan-login.rate_limit.max_attempts' => 2,
        'scan-login.rate_limit.decay_minutes' => 1,
    ]);

    $service = app(RateLimitService::class);
    $request = request();

    // 用完所有尝试次数
    $service->shouldLimit($request, 'test_action');
    $service->shouldLimit($request, 'test_action');

    expect($service->shouldLimit($request, 'test_action'))->toBeTrue();

    // 清除限制
    $service->clear($request, 'test_action');

    // 应该可以再次请求
    expect($service->shouldLimit($request, 'test_action'))->toBeFalse();
});

it('respects whitelist', function () {
    config([
        'scan-login.rate_limit.whitelist' => ['127.0.0.1'],
    ]);

    $service = app(RateLimitService::class);
    $request = request();

    expect($service->isWhitelisted($request))->toBeTrue();
});

it('respects blacklist', function () {
    config([
        'scan-login.rate_limit.blacklist' => ['192.168.1.100'],
    ]);

    $service = app(RateLimitService::class);
    $request = request();
    $request->server->set('REMOTE_ADDR', '192.168.1.100');

    expect($service->isBlacklisted($request))->toBeTrue();
});

it('can be disabled via config', function () {
    config(['scan-login.rate_limit.enabled' => false]);

    $service = app(RateLimitService::class);
    $request = request();

    // 即使超过限制，也应该返回 false（不限制）
    for ($i = 0; $i < 100; $i++) {
        expect($service->shouldLimit($request, 'test_action'))->toBeFalse();
    }
});

it('uses different limits for different actions', function () {
    config([
        'scan-login.rate_limit.max_attempts' => 10,
        'scan-login.rate_limit.actions' => [
            'qr_code_generation' => ['max_attempts' => 20],
            'token_claim' => ['max_attempts' => 5],
        ],
    ]);

    $service = app(RateLimitService::class);
    $request = request();

    // qr_code_generation 应该有 20 次尝试
    for ($i = 0; $i < 20; $i++) {
        $service->shouldLimit($request, 'qr_code_generation');
    }
    expect($service->shouldLimit($request, 'qr_code_generation'))->toBeTrue();

    // token_claim 应该有 5 次尝试（使用新的 request 实例）
    Cache::flush();
    for ($i = 0; $i < 5; $i++) {
        $service->shouldLimit($request, 'token_claim');
    }
    expect($service->shouldLimit($request, 'token_claim'))->toBeTrue();
});

it('uses different strategies', function () {
    $service = app(RateLimitService::class);
    $request = request();

    // IP 策略
    config(['scan-login.rate_limit.strategy' => 'ip']);
    $service->shouldLimit($request, 'test');
    expect($service->remainingAttempts($request, 'test'))->toBeLessThan(10);

    // 清除并测试用户策略
    Cache::flush();
    config(['scan-login.rate_limit.strategy' => 'user']);
    $service->shouldLimit($request, 'test');
    expect($service->remainingAttempts($request, 'test'))->toBeLessThan(10);
});

it('prevents token creation spam', function () {
    config([
        'scan-login.rate_limit.enabled' => true,
        'scan-login.rate_limit.max_attempts' => 3,
    ]);

    $service = app(ScanLoginTokenService::class);
    $rateLimitService = app(RateLimitService::class);

    // 创建 3 个 token
    for ($i = 0; $i < 3; $i++) {
        $service->createToken();
    }

    // 第 4 次应该被速率限制阻止
    expect($rateLimitService->shouldLimit(request(), 'qr_code_generation'))->toBeTrue();
});
