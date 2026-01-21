<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Post extends BaseModel
{
    protected $fillable = [
        'site_id',
        'type',
        'title',
        'slug',
        'excerpt',
        'content',
        'seo_title',
        'seo_description',
        'featured_image',
        'status',
        'scheduled_at',
        'published_at',
        'generation_prompt',
        'ai_provider',
        'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * Post types
     */
    public const TYPE_ARTICLE = 'article';
    public const TYPE_NEWS = 'news';
    public const TYPE_ANNOUNCEMENT = 'announcement';
    public const TYPE_FAQ = 'faq';

    /**
     * Status constants
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ERROR = 'error';

    /**
     * Get post types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_ARTICLE => 'Статья',
            self::TYPE_NEWS => 'Новость',
            self::TYPE_ANNOUNCEMENT => 'Анонс',
            self::TYPE_FAQ => 'FAQ',
        ];
    }

    /**
     * Get statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_GENERATING => 'Генерация',
            self::STATUS_SCHEDULED => 'Запланирован',
            self::STATUS_PUBLISHED => 'Опубликован',
            self::STATUS_ERROR => 'Ошибка',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('title') && !$post->isDirty('slug')) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    /**
     * Site relationship
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Check if post is published
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Check if post is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED && $this->scheduled_at !== null;
    }

    /**
     * Check if post is ready to publish
     */
    public function isReadyToPublish(): bool
    {
        return $this->isScheduled() && $this->scheduled_at->isPast();
    }

    /**
     * Publish the post
     */
    public function publish(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Get URL
     */
    public function getUrlAttribute(): string
    {
        $baseUrl = $this->site?->url ?? '';
        $section = match ($this->type) {
            self::TYPE_NEWS => 'news',
            self::TYPE_FAQ => 'faq',
            default => 'blog',
        };
        return "{$baseUrl}/{$section}/{$this->slug}";
    }

    /**
     * Get excerpt (auto-generate if empty)
     */
    public function getExcerptAttribute(?string $value): string
    {
        if (!empty($value)) {
            return $value;
        }
        return Str::limit(strip_tags($this->content ?? ''), 160);
    }

    /**
     * Scope published
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope scheduled
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope ready to publish
     */
    public function scopeReadyToPublish(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope by type
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by site
     */
    public function scopeSite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }
}
