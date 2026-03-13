<?php

namespace Wuwx\LaravelScanLogin\States;

class ScanLoginTokenStateClaimed extends ScanLoginTokenState
{
    public static $name = 'claimed';

    public function getColor(): string
    {
        return 'yellow';
    }

    public function getDescription(): string
    {
        return __('scan-login::scan-login.status.claimed');
    }
}
