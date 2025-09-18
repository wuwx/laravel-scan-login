<?php

namespace Wuwx\LaravelScanLogin\Commands;

use Illuminate\Console\Command;

class LaravelScanLoginCommand extends Command
{
    public $signature = 'laravel-scan-login';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
