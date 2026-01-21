<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Domain extends BaseModel
{
    protected $fillable = [
        'domain',
        'dns_account_id',
        'server_id',
        'status',
        'ssl_status',
        'cloudflare_zone_id',
        'dnspod_domain_id',
        'nameservers',
        'dr_rating',
        'iks_rating',
        'purchase_date',
        'purchase_price',
        'registrar',
        'expiry_date',
        'notes',
        'settings',
        'last_check_at',
        'error_message',
    ];

    protected $casts = [
        'dr_rating' => 'integer',
        'iks_rating' => 'integer',
        'purchase_price' => 'decimal:2',
        'purchase_date' => 'date',
        'expiry_date' => 'date',
        'settings' => 'array',
        'nameservers' => 'array',
        'last_check_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_DNS_CONFIGURING = 'dns_configuring';
    public const STATUS_SSL_PENDING = 'ssl_pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ERROR = 'error';
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * SSL Status constants
     */
    public const SSL_NONE = 'none';
    public const SSL_PENDING = 'pending';
    public const SSL_ACTIVE = 'active';
    public const SSL_ERROR = 'error';

    /**
     * Get all statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Ожидает',
            self::STATUS_DNS_CONFIGURING => 'Настройка DNS',
            self::STATUS_SSL_PENDING => 'Ожидает SSL',
            self::STATUS_ACTIVE => 'Активен',
            self::STATUS_ERROR => 'Ошибка',
            self::STATUS_SUSPENDED => 'Приостановлен',
        ];
    }

    /**
     * Get SSL statuses
     */
    public static function getSslStatuses(): array
    {
        return [
            self::SSL_NONE => 'Нет',
            self::SSL_PENDING => 'Ожидает',
            self::SSL_ACTIVE => 'Активен',
            self::SSL_ERROR => 'Ошибка',
        ];
    }

    /**
     * DNS Account relationship
     */
    public function dnsAccount(): BelongsTo
    {
        return $this->belongsTo(DnsAccount::class);
    }

    /**
     * Server relationship
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Site relationship
     */
    public function site(): HasOne
    {
        return $this->hasOne(Site::class);
    }

    /**
     * Check if domain has a site
     */
    public function hasSite(): bool
    {
        return $this->site()->exists();
    }

    /**
     * Check if domain is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if SSL is active
     */
    public function hasSsl(): bool
    {
        return $this->ssl_status === self::SSL_ACTIVE;
    }

    /**
     * Check if domain is available for site creation
     */
    public function isAvailableForSite(): bool
    {
        return $this->isActive() && !$this->hasSite();
    }

    /**
     * Get full URL
     */
    public function getUrlAttribute(): string
    {
        $protocol = $this->hasSsl() ? 'https' : 'http';
        return "{$protocol}://{$this->domain}";
    }

    /**
     * Set error status with message
     */
    public function setError(string $message): void
    {
        $this->update([
            'status' => self::STATUS_ERROR,
            'error_message' => $message,
            'last_check_at' => now(),
        ]);
    }

    /**
     * Clear error
     */
    public function clearError(): void
    {
        $this->update([
            'error_message' => null,
        ]);
    }

    /**
     * Scope for available domains (active without site)
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereDoesntHave('site');
    }

    /**
     * Scope by status
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by DNS account
     */
    public function scopeDnsAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('dns_account_id', $accountId);
    }

    /**
     * Scope by server
     */
    public function scopeServer(Builder $query, int $serverId): Builder
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * Scope with errors
     */
    public function scopeWithErrors(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ERROR);
    }

    /**
     * Search scope
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('domain', 'ILIKE', "%{$search}%");
    }
}
