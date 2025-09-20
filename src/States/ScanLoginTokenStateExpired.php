<?php

namespace Wuwx\LaravelScanLogin\States;

class ScanLoginTokenStateExpired extends ScanLoginTokenState
{
    public function getColor(): string
    {
        return 'gray';
    }

    public function getDescription(): string
    {
        return '已过期';
    }
}
