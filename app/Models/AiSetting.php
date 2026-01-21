<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Builder;

class AiSetting extends BaseModel
{
    protected $fillable = [
        'provider',
        'name',
        'api_key',
        'api_endpoint',
        'model',
        'max_tokens',
        'temperature',
        'is_default',
        'is_active',
        'rate_limit',
        'daily_limit',
        'requests_today',
        'last_request_at',
        'settings',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'max_tokens' => 'integer',
        'temperature' => 'float',
        'rate_limit' => 'integer',
        'daily_limit' => 'integer',
        'requests_today' => 'integer',
        'last_request_at' => 'datetime',
        'settings' => 'array',
    ];

    protected $hidden = [
        'api_key',
    ];

    /**
     * Provider constants
     */
    public const PROVIDER_ANTHROPIC = 'anthropic';
    public const PROVIDER_OPENAI = 'openai';
    public const PROVIDER_DEEPSEEK = 'deepseek';

    /**
     * Default models
     */
    public const MODEL_CLAUDE_OPUS_45 = 'claude-opus-4-5-20250514';
    public const MODEL_CLAUDE_SONNET_45 = 'claude-sonnet-4-5-20250514';
    public const MODEL_CLAUDE_SONNET_4 = 'claude-sonnet-4-20250514';
    public const MODEL_CLAUDE_HAIKU = 'claude-3-5-haiku-20241022';
    public const MODEL_GPT4O = 'gpt-4o';
    public const MODEL_GPT4O_MINI = 'gpt-4o-mini';
    public const MODEL_O1 = 'o1';
    public const MODEL_O1_MINI = 'o1-mini';
    public const MODEL_O3_MINI = 'o3-mini';
    public const MODEL_DEEPSEEK_CHAT = 'deepseek-chat';
    public const MODEL_DEEPSEEK_REASONER = 'deepseek-reasoner';

    /**
     * Get providers
     */
    public static function getProviders(): array
    {
        return [
            self::PROVIDER_ANTHROPIC => 'Anthropic (Claude)',
            self::PROVIDER_OPENAI => 'OpenAI (GPT)',
            self::PROVIDER_DEEPSEEK => 'DeepSeek',
        ];
    }

    /**
     * Get models by provider
     */
    public static function getModelsByProvider(string $provider): array
    {
        return match ($provider) {
            self::PROVIDER_ANTHROPIC => [
                self::MODEL_CLAUDE_OPUS_45 => 'Claude Opus 4.5 (самый умный)',
                self::MODEL_CLAUDE_SONNET_45 => 'Claude Sonnet 4.5 (рекомендуется)',
                self::MODEL_CLAUDE_SONNET_4 => 'Claude Sonnet 4',
                self::MODEL_CLAUDE_HAIKU => 'Claude 3.5 Haiku (быстрый)',
            ],
            self::PROVIDER_OPENAI => [
                self::MODEL_O3_MINI => 'o3-mini (новейший)',
                self::MODEL_O1 => 'o1 (reasoning)',
                self::MODEL_O1_MINI => 'o1-mini',
                self::MODEL_GPT4O => 'GPT-4o (рекомендуется)',
                self::MODEL_GPT4O_MINI => 'GPT-4o Mini (дешевле)',
            ],
            self::PROVIDER_DEEPSEEK => [
                self::MODEL_DEEPSEEK_REASONER => 'DeepSeek R1 (reasoning)',
                self::MODEL_DEEPSEEK_CHAT => 'DeepSeek Chat (V3)',
            ],
            default => [],
        };
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure only one default per provider
        static::saving(function ($setting) {
            if ($setting->is_default) {
                static::where('id', '!=', $setting->id ?? 0)
                    ->where('provider', $setting->provider)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        // Reset daily counter at midnight
        static::retrieved(function ($setting) {
            if ($setting->last_request_at && !$setting->last_request_at->isToday()) {
                $setting->update(['requests_today' => 0]);
            }
        });
    }

    /**
     * Encrypt API key
     */
    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt API key
     */
    public function getApiKeyAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Check if can make request (rate limiting)
     */
    public function canMakeRequest(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check daily limit
        if ($this->daily_limit && $this->requests_today >= $this->daily_limit) {
            return false;
        }

        // Check rate limit (requests per minute)
        if ($this->rate_limit && $this->last_request_at) {
            $secondsSinceLastRequest = now()->timestamp - $this->last_request_at->timestamp;
            $minSecondsBetweenRequests = 60 / $this->rate_limit;
            if ($secondsSinceLastRequest < $minSecondsBetweenRequests) {
                return false;
            }
        }

        return true;
    }

    /**
     * Record request
     */
    public function recordRequest(): void
    {
        $this->increment('requests_today');
        $this->update(['last_request_at' => now()]);
    }

    /**
     * Get the AI service class
     */
    public function getServiceClass(): string
    {
        return match ($this->provider) {
            self::PROVIDER_ANTHROPIC => \App\Services\AI\AnthropicService::class,
            self::PROVIDER_OPENAI => \App\Services\AI\OpenAIService::class,
            self::PROVIDER_DEEPSEEK => \App\Services\AI\DeepSeekService::class,
            default => throw new \InvalidArgumentException("Unknown provider: {$this->provider}"),
        };
    }

    /**
     * Get default setting for provider
     */
    public static function getDefault(?string $provider = null): ?self
    {
        $query = static::where('is_active', true)->where('is_default', true);
        
        if ($provider) {
            $query->where('provider', $provider);
        }

        return $query->first() ?? static::where('is_active', true)->first();
    }

    /**
     * Scope by provider
     */
    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope available (active and not rate limited)
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('daily_limit')
                    ->orWhereRaw('requests_today < daily_limit');
            });
    }
}
