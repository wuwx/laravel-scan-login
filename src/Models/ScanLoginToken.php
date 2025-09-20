<?php

namespace Wuwx\LaravelScanLogin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Wuwx\LaravelScanLogin\Database\Factories\ScanLoginTokenFactory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\ModelStates\HasStates;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled;
use Wuwx\LaravelScanLogin\States\ScanLoginTokenState;

class ScanLoginToken extends Model
{
    use HasFactory, Prunable, HasStates;
    
    protected $table = 'scan_login_tokens';

    protected $fillable = [
        'token',
        'status',
        'claimer_id',
        'consumer_id',
        'expires_at',
        'claimed_at',
        'consumed_at',
        'cancelled_at',
        // 生成二维码时的设备信息
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'claimed_at' => 'datetime',
        'consumed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'status' => ScanLoginTokenState::class,
    ];

    /**
     * Configure the state field.
     */
    protected function registerStates(): void
    {
        $this
            ->addState('status', ScanLoginTokenState::class)
            ->default(ScanLoginTokenStatePending::class);
    }



    /**
     * Mark the token as claimed (QR code was scanned).
     */
    public function markAsClaimed(int $claimerId): void
    {
        $this->update([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed',
            'claimer_id' => $claimerId,
            'claimed_at' => now(),
        ]);
    }

    /**
     * Mark the token as consumed (login completed).
     */
    public function markAsConsumed(int $consumerId): void
    {
        $this->update([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed',
            'consumer_id' => $consumerId,
            'consumed_at' => now(),
        ]);
    }

    /**
     * Mark the token as expired.
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired',
        ]);
    }

    /**
     * Mark the token as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateCancelled',
            'cancelled_at' => now(),
        ]);
    }


    /**
     * Scope to get only issued tokens.
     */
    public function scopeIssued($query)
    {
        return $query->where('status', 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to get only scanned tokens.
     */
    public function scopeScanned($query)
    {
        return $query->where('status', 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to get only user selected tokens.
     */
    public function scopeUserSelected($query)
    {
        return $query->where('status', 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to get only confirmed tokens.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired tokens.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($query) {
            $query->where('status', 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired')
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
            $query->where('status', 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired')
                  ->orWhere('expires_at', '<=', now());
        });
    }

    /**
     * Mark expired tokens before pruning.
     */
    public function pruning()
    {
        // Mark tokens that are past expiry time as expired
        static::where('expires_at', '<=', now())
            ->where('status', '!=', 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateExpired')
            ->get()
            ->each(function ($token) {
                $token->markAsExpired();
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
            'status' => 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
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

        return in_array($tokenRecord->status->getMorphClass(), [
            'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
            'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed',
            'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed'
        ]) && $tokenRecord->expires_at->isFuture();
    }

    /**
     * Mark a token as used by a specific user.
     */
    public static function markTokenAsUsed(string $token, int $userId): bool
    {
        $tokenRecord = static::where('token', $token)
            ->whereIn('status', [
                'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending',
                'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed',
                'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed'
            ])
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$tokenRecord) {
            return false;
        }

        $tokenRecord->markAsConsumed($userId);
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

        return $tokenRecord->status->getMorphClass();
    }


    /**
     * Cancel a token.
     */
    public static function cancelToken(string $token): bool
    {
        $tokenRecord = static::where('token', $token)
            ->whereIn('status', [
                'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateClaimed',
                'Wuwx\LaravelScanLogin\States\ScanLoginTokenStateConsumed'
            ])
            ->first();
        
        if (!$tokenRecord) {
            return false;
        }

        $tokenRecord->markAsCancelled();
        return true;
    }



    /**
     * Mark a token as claimed by a specific user.
     */
    public static function markTokenAsClaimed(string $token, int $claimerId): bool
    {
        $tokenRecord = static::where('token', $token)
            ->where('status', 'Wuwx\LaravelScanLogin\States\ScanLoginTokenStatePending')
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$tokenRecord) {
            return false;
        }

        $tokenRecord->markAsClaimed($claimerId);
        return true;
    }

    /**
     * Mark a token as user selected (scanner selected which user to login).
     */
    public static function markTokenAsUserSelected(string $token, int $selectedUserId): bool
    {
        $tokenRecord = static::where('token', $token)
            ->where('status', 'scanned')
            ->where('expires_at', '>', now())
            ->first();
        
        if (!$tokenRecord) {
            return false;
        }

        $tokenRecord->markAsConsumed($selectedUserId);
        return true;
    }

    /**
     * Get available users for the scanner to choose from.
     * This method should be implemented based on your user management system.
     */
    public static function getAvailableUsersForScanner(int $scannedBy): array
    {
        // 这里需要根据你的用户管理系统来实现
        // 例如：返回扫码者可以管理的用户列表
        // 可能通过角色、权限、或者直接的用户关联关系来确定
        
        // 示例实现（需要根据实际业务逻辑调整）：
        return [
            ['id' => 1, 'name' => '用户1', 'email' => 'user1@example.com'],
            ['id' => 2, 'name' => '用户2', 'email' => 'user2@example.com'],
            ['id' => 3, 'name' => '用户3', 'email' => 'user3@example.com'],
        ];
    }
}