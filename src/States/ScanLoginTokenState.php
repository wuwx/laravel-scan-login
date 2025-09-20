<?php

namespace Wuwx\LaravelScanLogin\States;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ScanLoginTokenState extends State
{
    /**
     * Get the color for this state (for UI display).
     */
    abstract public function getColor(): string;

    /**
     * Get the description for this state.
     */
    abstract public function getDescription(): string;

    /**
     * Configure state transitions.
     */
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(ScanLoginTokenStatePending::class)
            ->allowTransition(ScanLoginTokenStatePending::class, ScanLoginTokenStateClaimed::class)
            ->allowTransition(ScanLoginTokenStatePending::class, ScanLoginTokenStateExpired::class)
            ->allowTransition(ScanLoginTokenStateClaimed::class, ScanLoginTokenStateConsumed::class)
            ->allowTransition(ScanLoginTokenStateClaimed::class, ScanLoginTokenStateExpired::class)
            ->allowTransition(ScanLoginTokenStateClaimed::class, ScanLoginTokenStateCancelled::class);
    }
}
