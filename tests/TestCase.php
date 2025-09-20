<?php

namespace Wuwx\LaravelScanLogin\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
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

        // 运行迁移
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
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
