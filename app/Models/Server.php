<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Server extends BaseModel
{
    protected $fillable = [
        'name',
        'ip_address',
        'ssh_host',
        'ssh_port',
        'ssh_user',
        'ssh_key',
        'ssh_password',
        'is_primary',
        'is_active',
        'max_domains',
        'nginx_config_path',
        'sites_path',
        'caddy_api_url',
        'settings',
        'last_health_check',
        'health_status',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'ssh_port' => 'integer',
        'max_domains' => 'integer',
        'settings' => 'array',
        'last_health_check' => 'datetime',
    ];

    protected $hidden = [
        'ssh_key',
        'ssh_password',
    ];

    /**
     * Health status constants
     */
    public const HEALTH_OK = 'ok';
    public const HEALTH_WARNING = 'warning';
    public const HEALTH_ERROR = 'error';
    public const HEALTH_UNKNOWN = 'unknown';

    /**
     * Default paths
     */
    public const DEFAULT_NGINX_CONFIG_PATH = '/etc/nginx/sites-enabled';
    public const DEFAULT_SITES_PATH = '/var/www/sites';
    public const DEFAULT_SSH_PORT = 22;

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($server) {
            // Set defaults
            $server->ssh_port = $server->ssh_port ?? self::DEFAULT_SSH_PORT;
            $server->nginx_config_path = $server->nginx_config_path ?? self::DEFAULT_NGINX_CONFIG_PATH;
            $server->sites_path = $server->sites_path ?? self::DEFAULT_SITES_PATH;
            $server->health_status = self::HEALTH_UNKNOWN;
        });

        // Ensure only one primary server
        static::saving(function ($server) {
            if ($server->is_primary) {
                static::where('id', '!=', $server->id ?? 0)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }

    /**
     * Encrypt SSH key before saving
     */
    public function setSshKeyAttribute(?string $value): void
    {
        $this->attributes['ssh_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt SSH key when retrieving
     */
    public function getSshKeyAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Encrypt SSH password before saving
     */
    public function setSshPasswordAttribute(?string $value): void
    {
        $this->attributes['ssh_password'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt SSH password when retrieving
     */
    public function getSshPasswordAttribute(?string $value): ?string
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
     * Get current domains count
     */
    public function getDomainsCountAttribute(): int
    {
        return $this->domains()->count();
    }

    /**
     * Check if server can accept more domains
     */
    public function canAcceptDomains(int $count = 1): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->max_domains === null) {
            return true;
        }

        return ($this->domains_count + $count) <= $this->max_domains;
    }

    /**
     * Get available slots
     */
    public function getAvailableSlotsAttribute(): ?int
    {
        if ($this->max_domains === null) {
            return null;
        }

        return max(0, $this->max_domains - $this->domains_count);
    }

    /**
     * Check if server is healthy
     */
    public function isHealthy(): bool
    {
        return $this->health_status === self::HEALTH_OK;
    }

    /**
     * Get the primary server
     */
    public static function primary(): ?self
    {
        return static::where('is_primary', true)->where('is_active', true)->first();
    }

    /**
     * Scope for available servers (active with slots)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('max_domains')
                    ->orWhereRaw('(SELECT COUNT(*) FROM domains WHERE domains.server_id = servers.id) < servers.max_domains');
            });
    }
}
