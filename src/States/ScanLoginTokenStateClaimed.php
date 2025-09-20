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
        return '已领用，等待选择用户';
    }
}
