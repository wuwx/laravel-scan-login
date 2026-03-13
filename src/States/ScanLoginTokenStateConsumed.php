<?php

namespace Wuwx\LaravelScanLogin\States;

class ScanLoginTokenStateConsumed extends ScanLoginTokenState
{
    public static $name = 'consumed';
    
    public function getColor(): string
    {
        return 'green';
    }

    public function getDescription(): string
    {
        return __('scan-login::scan-login.status.consumed');
    }
}
