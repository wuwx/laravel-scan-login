<?php

namespace Wuwx\LaravelScanLogin\States;

class ScanLoginTokenStatePending extends ScanLoginTokenState
{
    public static $name = 'pending';
    
    public function getColor(): string
    {
        return 'blue';
    }

    public function getDescription(): string
    {
        return __('scan-login::scan-login.status.pending');
    }
}
