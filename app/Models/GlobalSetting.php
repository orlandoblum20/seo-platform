<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class GlobalSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Value types
     */
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_FLOAT = 'float';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_ARRAY = 'array';
    public const TYPE_JSON = 'json';

    /**
     * Setting groups
     */
    public const GROUP_GENERAL = 'general';
    public const GROUP_SEO = 'seo';
    public const GROUP_KEITARO = 'keitaro';
    public const GROUP_ANALYTICS = 'analytics';
    public const GROUP_CONTENT = 'content';

    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'global_setting:';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get setting value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            return $setting->getCastedValue();
        });
    }

    /**
     * Set setting value
     */
    public static function set(string $key, mixed $value, ?string $type = null, ?string $group = null): void
    {
        $type = $type ?? self::detectType($value);
        
        $setting = static::firstOrNew(['key' => $key]);
        $setting->type = $type;
        $setting->group = $group ?? $setting->group ?? self::GROUP_GENERAL;
        $setting->setValue($value);
        $setting->save();

        // Clear cache
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * Delete setting
     */
    public static function remove(string $key): void
    {
        static::where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * Get all settings in a group
     */
    public static function getGroup(string $group): array
    {
        $settings = static::where('group', $group)->get();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->getCastedValue();
        }

        return $result;
    }

    /**
     * Detect value type
     */
    private static function detectType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => self::TYPE_BOOLEAN,
            is_int($value) => self::TYPE_INTEGER,
            is_float($value) => self::TYPE_FLOAT,
            is_array($value) => self::TYPE_ARRAY,
            default => self::TYPE_STRING,
        };
    }

    /**
     * Set value with proper encoding
     */
    public function setValue(mixed $value): void
    {
        $encoded = match ($this->type) {
            self::TYPE_ARRAY, self::TYPE_JSON => json_encode($value),
            self::TYPE_BOOLEAN => $value ? '1' : '0',
            default => (string) $value,
        };

        if ($this->is_encrypted) {
            $encoded = Crypt::encryptString($encoded);
        }

        $this->value = $encoded;
    }

    /**
     * Get casted value
     */
    public function getCastedValue(): mixed
    {
        $value = $this->value;

        if ($this->is_encrypted && $value) {
            $value = Crypt::decryptString($value);
        }

        return match ($this->type) {
            self::TYPE_INTEGER => (int) $value,
            self::TYPE_FLOAT => (float) $value,
            self::TYPE_BOOLEAN => $value === '1' || $value === 'true',
            self::TYPE_ARRAY, self::TYPE_JSON => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    /**
     * Clear all cached settings
     */
    public static function clearCache(): void
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        }
    }

    /**
     * Default settings configuration
     */
    public static function getDefaults(): array
    {
        return [
            // General
            'app_name' => ['value' => 'SEO Landing Platform', 'type' => self::TYPE_STRING, 'group' => self::GROUP_GENERAL],
            'timezone' => ['value' => 'Europe/Moscow', 'type' => self::TYPE_STRING, 'group' => self::GROUP_GENERAL],
            
            // Keitaro
            'keitaro_enabled' => ['value' => false, 'type' => self::TYPE_BOOLEAN, 'group' => self::GROUP_KEITARO],
            'keitaro_url' => ['value' => '', 'type' => self::TYPE_STRING, 'group' => self::GROUP_KEITARO],
            'keitaro_campaign_id' => ['value' => '', 'type' => self::TYPE_STRING, 'group' => self::GROUP_KEITARO],
            
            // Analytics
            'global_yandex_metrika' => ['value' => '', 'type' => self::TYPE_STRING, 'group' => self::GROUP_ANALYTICS],
            'global_google_analytics' => ['value' => '', 'type' => self::TYPE_STRING, 'group' => self::GROUP_ANALYTICS],
            'global_gtm' => ['value' => '', 'type' => self::TYPE_STRING, 'group' => self::GROUP_ANALYTICS],
            
            // SEO
            'default_robots_txt' => ['value' => "User-agent: *\nAllow: /", 'type' => self::TYPE_STRING, 'group' => self::GROUP_SEO],
            'generate_sitemap' => ['value' => true, 'type' => self::TYPE_BOOLEAN, 'group' => self::GROUP_SEO],
            
            // Content
            'content_humanize' => ['value' => true, 'type' => self::TYPE_BOOLEAN, 'group' => self::GROUP_CONTENT],
            'content_variation_level' => ['value' => 3, 'type' => self::TYPE_INTEGER, 'group' => self::GROUP_CONTENT],
        ];
    }

    /**
     * Initialize default settings
     */
    public static function initializeDefaults(): void
    {
        foreach (self::getDefaults() as $key => $config) {
            if (!static::where('key', $key)->exists()) {
                static::set($key, $config['value'], $config['type'], $config['group']);
            }
        }
    }
}
