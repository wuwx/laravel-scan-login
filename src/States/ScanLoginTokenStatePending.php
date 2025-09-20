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
        return '待处理，等待扫码';
    }
}
