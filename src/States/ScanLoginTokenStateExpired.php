<?php

namespace Wuwx\LaravelScanLogin\States;

class ScanLoginTokenStateExpired extends ScanLoginTokenState
{
    public static $name = 'expired';
    
    public function getColor(): string
    {
        return 'gray';
    }

    public function getDescription(): string
    {
        return __('scan-login::scan-login.status.expired');
    }
}
