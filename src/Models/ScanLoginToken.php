<?php

namespace Wuwx\LaravelScanLogin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;
use Wuwx\LaravelScanLogin\Database\Factories\ScanLoginTokenFactory;
use Carbon\Carbon;

class ScanLoginToken extends Model
{
    use HasFactory;
    
    protected $table = 'scan_login_tokens';

    protected $fillable = [
        'token',
        'status',
        'user_id',
        'expires_at',
        'used_at',
        // 生成二维码时的设备信息
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', User::class));
    }

    /**
     * Check if the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the token is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Mark the token as used by a specific user.
     */
    public function markAsUsed(int $userId): void
    {
        $this->update([
            'status' => 'used',
            'user_id' => $userId,
            'used_at' => now(),
        ]);
    }

    /**
     * Mark the token as expired.
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Scope to get only pending tokens.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired tokens.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($query) {
            $query->where('status', 'expired')
                  ->orWhere('expires_at', '<=', now());
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ScanLoginTokenFactory::new();
    }

    /**
     * Get the device information for the token generator.
     */
    public function getDeviceInfo(): array
    {
        return [
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
        ];
    }
}