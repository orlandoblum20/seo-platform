<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Site extends BaseModel
{
    protected $fillable = [
        'domain_id',
        'template_id',
        'status',
        'title',
        'niche',
        'keywords',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'content',
        'settings',
        'color_scheme',
        'analytics_codes',
        'custom_css',
        'custom_js',
        'custom_head',
        'keitaro_enabled',
        'generation_started_at',
        'generation_completed_at',
        'published_at',
        'unpublished_at',
        'error_message',
    ];

    protected $casts = [
        'keywords' => 'array',
        'content' => 'array',
        'settings' => 'array',
        'color_scheme' => 'array',
        'analytics_codes' => 'array',
        'keitaro_enabled' => 'boolean',
        'generation_started_at' => 'datetime',
        'generation_completed_at' => 'datetime',
        'published_at' => 'datetime',
        'unpublished_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_PUBLISHING = 'publishing';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_UNPUBLISHED = 'unpublished';
    public const STATUS_ERROR = 'error';

    /**
     * Get all statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_GENERATING => 'Генерация',
            self::STATUS_GENERATED => 'Сгенерирован',
            self::STATUS_PUBLISHING => 'Публикация',
            self::STATUS_PUBLISHED => 'Опубликован',
            self::STATUS_UNPUBLISHED => 'Снят с публикации',
            self::STATUS_ERROR => 'Ошибка',
        ];
    }

    /**
     * Domain relationship
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Template relationship
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Posts relationship
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Backlinks relationship (many-to-many through pivot)
     */
    public function backlinks(): BelongsToMany
    {
        return $this->belongsToMany(Backlink::class, 'site_backlinks')
            ->withPivot(['anchor', 'placement', 'is_nofollow', 'custom_html'])
            ->withTimestamps();
    }

    /**
     * Autopost settings relationship
     */
    public function autopostSettings(): HasOne
    {
        return $this->hasOne(AutopostSetting::class);
    }

    /**
     * Check if site is published
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Check if site can be published
     */
    public function canBePublished(): bool
    {
        return in_array($this->status, [
            self::STATUS_GENERATED,
            self::STATUS_UNPUBLISHED,
        ]) && $this->domain?->isActive();
    }

    /**
     * Check if site can be regenerated
     */
    public function canBeRegenerated(): bool
    {
        return !in_array($this->status, [
            self::STATUS_GENERATING,
            self::STATUS_PUBLISHING,
        ]);
    }

    /**
     * Get URL
     */
    public function getUrlAttribute(): ?string
    {
        return $this->domain?->url;
    }

    /**
     * Get domain name
     */
    public function getDomainNameAttribute(): ?string
    {
        return $this->domain?->domain;
    }

    /**
     * Get content for section
     */
    public function getContentSection(string $page, string $section): ?array
    {
        $content = $this->content ?? [];
        return $content[$page][$section] ?? null;
    }

    /**
     * Set content for section
     */
    public function setContentSection(string $page, string $section, array $data): void
    {
        $content = $this->content ?? [];
        $content[$page][$section] = $data;
        $this->content = $content;
    }

    /**
     * Get analytics code by type
     */
    public function getAnalyticsCode(string $type): ?string
    {
        $codes = $this->analytics_codes ?? [];
        return $codes[$type] ?? null;
    }

    /**
     * Set analytics code
     */
    public function setAnalyticsCode(string $type, ?string $code): void
    {
        $codes = $this->analytics_codes ?? [];
        if ($code === null) {
            unset($codes[$type]);
        } else {
            $codes[$type] = $code;
        }
        $this->analytics_codes = $codes;
    }

    /**
     * Get published posts count
     */
    public function getPublishedPostsCountAttribute(): int
    {
        return $this->posts()->where('status', Post::STATUS_PUBLISHED)->count();
    }

    /**
     * Scope published
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope by status
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by template
     */
    public function scopeTemplate(Builder $query, int $templateId): Builder
    {
        return $query->where('template_id', $templateId);
    }

    /**
     * Scope with autopost enabled
     */
    public function scopeWithAutopost(Builder $query): Builder
    {
        return $query->whereHas('autopostSettings', function ($q) {
            $q->where('is_enabled', true);
        });
    }

    /**
     * Search scope
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'ILIKE', "%{$search}%")
                ->orWhere('niche', 'ILIKE', "%{$search}%")
                ->orWhereHas('domain', function ($dq) use ($search) {
                    $dq->where('domain', 'ILIKE', "%{$search}%");
                });
        });
    }
}
