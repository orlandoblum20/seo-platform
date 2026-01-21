<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    public $timestamps = false;

    protected $table = 'login_history';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'status',
        'failure_reason',
        'logged_in_at',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_2FA_PENDING = '2fa_pending';
    public const STATUS_2FA_FAILED = '2fa_failed';

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a login record
     */
    public static function record(
        int $userId,
        string $status,
        ?string $failureReason = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => $status,
            'failure_reason' => $failureReason,
            'logged_in_at' => now(),
        ]);
    }

    /**
     * Scope for successful logins
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope for failed logins
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', [
            self::STATUS_FAILED,
            self::STATUS_BLOCKED,
            self::STATUS_2FA_FAILED,
        ]);
    }
}
