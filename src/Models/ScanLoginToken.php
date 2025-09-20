<?php

namespace Wuwx\LaravelScanLogin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Foundation\Auth\User;
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
     * Prepare the model for pruning.
     * This method is called before each model is deleted during pruning.
     */
    protected function pruning()
    {
        // Optional: Add any cleanup logic before deletion
        // For example, logging, notifications, etc.
    }

    /**
     * Create a new login token.
     */
    public static function createToken(Request $request = null, array $deviceInfo = null): string
    {
        $token = Str::random(64);
        $expiryMinutes = config('scan-login.token_expiry_minutes', 5);
        $expiresAt = now()->addMinutes($expiryMinutes);
        
        // Extract device information
        $deviceData = static::extractDeviceInfo($request, $deviceInfo);
        
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
     * Get the user ID associated with a used token.
     */
    public static function getTokenUserId(string $token): ?int
    {
        $tokenRecord = static::where('token', $token)
            ->where('status', 'used')
            ->first();
        
        return $tokenRecord ? $tokenRecord->user_id : null;
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
     * Extract device information for token generation.
     */
    private static function extractDeviceInfo(Request $request = null, array $deviceInfo = null): array
    {
        if ($deviceInfo) {
            return [
                'ip_address' => $deviceInfo['ip_address'] ?? null,
                'user_agent' => $deviceInfo['user_agent'] ?? null,
            ];
        }

        if ($request) {
            return [
                'ip_address' => static::getClientIp($request),
                'user_agent' => $request->userAgent(),
            ];
        }

        return [];
    }

    /**
     * Get the client IP address.
     */
    private static function getClientIp(Request $request): string
    {
        // Check for IP from various headers (for load balancers, proxies, etc.)
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipHeaders as $header) {
            if ($request->server($header)) {
                $ip = $request->server($header);
                // Handle comma-separated IPs (take the first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->ip() ?? '0.0.0.0';
    }

    /**
     * Generate the login URL for the given token.
     */
    public static function generateLoginUrl(string $token): string
    {
        return url(route('scan-login.mobile-login', ['token' => $token]));
    }
}