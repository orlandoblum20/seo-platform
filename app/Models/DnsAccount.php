<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class DnsAccount extends BaseModel
{
    protected $fillable = [
        'name',
        'provider',
        'api_key',
        'api_secret',
        'email',
        'account_id',
        'is_active',
        'settings',
        'last_sync_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    /**
     * Provider constants
     */
    public const PROVIDER_CLOUDFLARE = 'cloudflare';
    public const PROVIDER_DNSPOD = 'dnspod';

    /**
     * Get available providers
     */
    public static function getProviders(): array
    {
        return [
            self::PROVIDER_CLOUDFLARE => 'Cloudflare',
            self::PROVIDER_DNSPOD => 'DNSPOD',
        ];
    }

    /**
     * Encrypt API key before saving
     */
    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt API key when retrieving
     */
    public function getApiKeyAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Encrypt API secret before saving
     */
    public function setApiSecretAttribute(?string $value): void
    {
        $this->attributes['api_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt API secret when retrieving
     */
    public function getApiSecretAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Domains relationship
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Get domains count
     */
    public function getDomainsCountAttribute(): int
    {
        return $this->domains()->count();
    }

    /**
     * Check if provider is Cloudflare
     */
    public function isCloudflare(): bool
    {
        return $this->provider === self::PROVIDER_CLOUDFLARE;
    }

    /**
     * Check if provider is DNSPOD
     */
    public function isDnspod(): bool
    {
        return $this->provider === self::PROVIDER_DNSPOD;
    }

    /**
     * Scope by provider
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Get the DNS service class for this account
     */
    public function getDnsService(): string
    {
        return match ($this->provider) {
            self::PROVIDER_CLOUDFLARE => \App\Services\DNS\CloudflareService::class,
            self::PROVIDER_DNSPOD => \App\Services\DNS\DnspodService::class,
            default => throw new \InvalidArgumentException("Unknown provider: {$this->provider}"),
        };
    }
}
