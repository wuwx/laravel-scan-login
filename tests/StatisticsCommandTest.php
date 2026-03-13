<?php

use Wuwx\LaravelScanLogin\Models\ScanLoginToken;

beforeEach(function () {
    // 清理测试数据
    ScanLoginToken::query()->delete();
});

it('displays statistics', function () {
    // 创建测试数据
    ScanLoginToken::factory()->count(5)->create([
        'created_at' => now()->subDays(5),
    ]);

    $this->artisan('scan-login:stats')
        ->expectsOutput('Scan Login Statistics (Last 30 days)')
        ->assertSuccessful();
});

it('can show statistics for custom period', function () {
    ScanLoginToken::factory()->count(3)->create([
        'created_at' => now()->subDays(5),
    ]);

    ScanLoginToken::factory()->count(2)->create([
        'created_at' => now()->subDays(10),
    ]);

    $this->artisan('scan-login:stats', ['--days' => 7])
        ->expectsOutput('Scan Login Statistics (Last 7 days)')
        ->assertSuccessful();
});

it('handles empty database', function () {
    $this->artisan('scan-login:stats')
        ->assertSuccessful();
});

it('shows status breakdown', function () {
    // 创建不同状态的 token
    ScanLoginToken::factory()->create(['state' => 'pending']);
    ScanLoginToken::factory()->create(['state' => 'consumed']);
    ScanLoginToken::factory()->create(['state' => 'expired']);

    $this->artisan('scan-login:stats')
        ->expectsOutput('Status Breakdown:')
        ->assertSuccessful();
});

it('calculates success rate', function () {
    // 创建 10 个 token，5 个成功
    ScanLoginToken::factory()->count(5)->create([
        'state' => 'consumed',
        'created_at' => now()->subDays(5),
    ]);

    ScanLoginToken::factory()->count(5)->create([
        'state' => 'expired',
        'created_at' => now()->subDays(5),
    ]);

    $this->artisan('scan-login:stats')
        ->expectsOutput('Success Metrics:')
        ->assertSuccessful();
});
