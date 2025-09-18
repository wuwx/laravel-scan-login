<?php

namespace Wuwx\LaravelScanLogin\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Wuwx\LaravelScanLogin\LaravelScanLogin
 */
class LaravelScanLogin extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Wuwx\LaravelScanLogin\LaravelScanLogin::class;
    }
}
