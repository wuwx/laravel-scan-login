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

        // 为所有测试创建必要的数据库表
        $this->setUpDatabase();
    }

    protected function defineDatabaseMigrations()
    {
        // Load package migrations for tests using RefreshDatabase
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * 为所有测试设置数据库表（不仅仅是使用 RefreshDatabase 的测试）
     */
    protected function setUpDatabase(): void
    {
        // 创建 users 表用于测试
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // 创建 scan_login_tokens 表
        if (! Schema::hasTable('scan_login_tokens')) {
            Schema::create('scan_login_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('token', 64)->unique();
                $table->string('state');
                $table->foreignId('claimer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('consumer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('device_type', 50)->nullable();
                $table->string('location')->nullable();
                $table->timestamp('expires_at');
                $table->timestamp('claimed_at')->nullable();
                $table->timestamp('consumed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index('token');
                $table->index('state');
                $table->index('expires_at');
            });
        }

        // 创建 migrations 表（用于某些测试）
        if (! Schema::hasTable('migrations')) {
            Schema::create('migrations', function (Blueprint $table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
            });
        }
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

        // 使用 array 缓存驱动
        config()->set('cache.default', 'array');
    }
}
