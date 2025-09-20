<?php

namespace Wuwx\LaravelScanLogin\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Wuwx\LaravelScanLogin\ScanLoginServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Wuwx\\LaravelScanLogin\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // 手动创建表（因为 .stub 文件在测试环境中可能不会被自动加载）
        Schema::create('scan_login_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('status')->default('Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending');
            $table->unsignedBigInteger('claimer_id')->nullable()->comment('扫码者用户ID');
            $table->unsignedBigInteger('consumer_id')->nullable()->comment('最终消费token的用户ID');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('claimed_at')->nullable()->comment('被扫码/领用时间');
            $table->timestamp('consumed_at')->nullable()->comment('已消费/完成登录时间');
            $table->timestamp('cancelled_at')->nullable()->comment('已取消时间');
            $table->timestamps();
        });
    }


    protected function getPackageProviders($app)
    {
        return [
            \Livewire\LivewireServiceProvider::class,
            ScanLoginServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('app.debug', true);
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // No cache configuration needed
    }
}
