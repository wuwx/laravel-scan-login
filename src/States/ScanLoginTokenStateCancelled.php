<?php

namespace Wuwx\LaravelScanLogin\States;

class ScanLoginTokenStateCancelled extends ScanLoginTokenState
{
    public static $name = 'cancelled';

    public function getColor(): string
    {
        return 'red';
    }

    public function getDescription(): string
    {
        return '已取消';
    }
}
