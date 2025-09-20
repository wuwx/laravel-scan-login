<?php

namespace Wuwx\LaravelScanLogin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\HasStates;
use Wuwx\LaravelScanLogin\Database\Factories\ScanLoginTokenFactory;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenState;

class ScanLoginToken extends Model
{
    use HasFactory, HasStates;

    protected $table = 'scan_login_tokens';

    protected $fillable = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'claimed_at' => 'datetime',
        'consumed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'state' => ScanLoginTokenState::class,
    ];


    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ScanLoginTokenFactory::new();
    }


}
