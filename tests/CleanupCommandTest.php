<?php

use Wuwx\LaravelScanLogin\Models\ScanLoginToken;

beforeEach(function () {
    // 清理测试数据
    ScanLoginToken::query()->delete();
});

it('can cleanup old tokens', function () {
    // 创建旧 token
    $oldToken = ScanLoginToken::factory()->create([
        'created_at' => now()->subDays(10),
    ]);

    // 创建新 token
    $newToken = ScanLoginToken::factory()->create([
        'created_at' => now()->subDays(3),
    ]);

    // 执行清理（删除 7 天前的）
    $this->artisan('scan-login:cleanup', ['--force' => true, '--days' => 7])
        ->assertSuccessful();

    // 验证旧 token 被删除
    expect(ScanLoginToken::find($oldToken->id))->toBeNull();

    // 验证新 token 保留
    expect(ScanLoginToken::find($newToken->id))->not->toBeNull();
});

it('shows confirmation prompt without force flag', function () {
    ScanLoginToken::factory()->create([
        'created_at' => now()->subDays(10),
    ]);

    $this->artisan('scan-login:cleanup', ['--days' => 7])
        ->expectsConfirmation('Do you want to delete 1 token(s)?', 'no')
        ->assertSuccessful();

    // Token 应该还在
    expect(ScanLoginToken::count())->toBe(1);
});

it('can filter by status', function () {
    // 创建不同状态的旧 token
    $expiredToken = ScanLoginToken::factory()->create([
        'created_at' => now()->subDays(10),
        'state' => 'expired',
    ]);

    $consumedToken = ScanLoginToken::factory()->create([
        'created_at' => now()->subDays(10),
        'state' => 'consumed',
    ]);

    // 只删除 expired 状态的
    $this->artisan('scan-login:cleanup', [
        '--force' => true,
        '--days' => 7,
        '--status' => 'expired',
    ])->assertSuccessful();

    // 验证只有 expired 被删除
    expect(ScanLoginToken::find($expiredToken->id))->toBeNull();
    expect(ScanLoginToken::find($consumedToken->id))->not->toBeNull();
});

it('supports dry run mode', function () {
    ScanLoginToken::factory()->count(5)->create([
        'created_at' => now()->subDays(10),
    ]);

    $this->artisan('scan-login:cleanup', ['--dry-run' => true, '--days' => 7])
        ->assertSuccessful();

    // 所有 token 应该还在
    expect(ScanLoginToken::count())->toBe(5);
});

it('validates days parameter', function () {
    $this->artisan('scan-login:cleanup', ['--days' => 0])
        ->assertFailed();

    $this->artisan('scan-login:cleanup', ['--days' => -1])
        ->assertFailed();
});

it('handles no tokens to cleanup', function () {
    $this->artisan('scan-login:cleanup', ['--force' => true])
        ->expectsOutput('No tokens found to clean up.')
        ->assertSuccessful();
});
