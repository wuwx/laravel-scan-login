<?php

namespace Wuwx\LaravelScanLogin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Wuwx\LaravelScanLogin\Database\Factories\ScanLoginTokenFactory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScanLoginToken extends Model
{
    use HasFactory, Prunable;
    
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
     * Get the prunable model query.
     * This method is used by the model:prune command to determine which records to delete.
     */
    public function prunable()
    {
        return static::where(function ($query) {
            $query->where('status', 'expired')
                  ->orWhere('expires_at', '<=', now());
        });
    }


    /**
     * Create a new login token.
     */
    public static function createToken(Request $request = null, array $deviceInfo = null): string
    {
        $token = Str::random(64);
        $expiryMinutes = config('scan-login.token_expiry_minutes', 5);
        $expiresAt = now()->addMinutes($expiryMinutes);
        
        // Prepare device information
        $deviceData = [];
        if ($deviceInfo) {
            $deviceData = [
                'ip_address' => $deviceInfo['ip_address'] ?? null,
                'user_agent' => $deviceInfo['user_agent'] ?? null,
            ];
        } elseif ($request) {
            $deviceData = [
                'ip_address' => $request->ip() ?? '0.0.0.0',
                'user_agent' => $request->userAgent(),
            ];
        }
        
        static::create(array_merge([
            'token' => $token,
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ], $deviceData));

        return $token;
    }

    /**
     * Validate if a token exists and is valid.
     */
    public static function validateToken(string $token): bool
    {
        $tokenRecord = static::where('token', $token)->first();
        
        if (!$tokenRecord) {
            return false;
        }

        return $tokenRecord->status === 'pending' && $tokenRecord->expires_at->isFuture();
    }

    /**
     * Mark a token as used by a specific user.
     */
    public static function markTokenAsUsed(string $token, int $userId): bool
    {
        $tokenRecord = static::where('token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$tokenRecord) {
            return false;
        }

        $tokenRecord->markAsUsed($userId);
        return true;
    }

    /**
     * Get the status of a token.
     */
    public static function getTokenStatus(string $token): string
    {
        $tokenRecord = static::where('token', $token)->first();
        
        if (!$tokenRecord) {
            return 'not_found';
        }

        if ($tokenRecord->expires_at->isPast()) {
            return 'expired';
        }

        return $tokenRecord->status;
    }


    /**
     * Cancel a token.
     */
    public static function cancelToken(string $token): bool
    {
        $tokenRecord = static::where('token', $token)
            ->where('status', 'pending')
            ->first();
        
        if (!$tokenRecord) {
            return false;
        }

        $tokenRecord->update(['status' => 'cancelled']);
        return true;
    }



    /**
     * Generate the login URL for the given token.
     */
    public static function generateLoginUrl(string $token): string
    {
        return url(route('scan-login.mobile-login', ['token' => $token]));
    }
}