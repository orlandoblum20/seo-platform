<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Template extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'preview_image',
        'structure',
        'default_prompts',
        'color_schemes',
        'seo_settings',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'structure' => 'array',
        'default_prompts' => 'array',
        'color_schemes' => 'array',
        'seo_settings' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Template types
     */
    public const TYPE_LANDING = 'landing';
    public const TYPE_BUSINESS = 'business';
    public const TYPE_SERVICE = 'service';
    public const TYPE_CORPORATE = 'corporate';
    public const TYPE_BLOG = 'blog';
    public const TYPE_CATALOG = 'catalog';
    public const TYPE_SHOP = 'shop';
    public const TYPE_PORTFOLIO = 'portfolio';

    /**
     * Get available types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_LANDING => 'Одностраничник',
            self::TYPE_BUSINESS => 'Бизнес',
            self::TYPE_SERVICE => 'Услуги',
            self::TYPE_CORPORATE => 'Корпоративный сайт',
            self::TYPE_BLOG => 'Блог',
            self::TYPE_CATALOG => 'Каталог',
            self::TYPE_SHOP => 'Интернет-магазин',
            self::TYPE_PORTFOLIO => 'Портфолио',
        ];
    }

    /**
     * Sites relationship
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /**
     * Get sites count
     */
    public function getSitesCountAttribute(): int
    {
        return $this->sites()->count();
    }

    /**
     * Get default color scheme
     */
    public function getDefaultColorScheme(): array
    {
        $schemes = $this->color_schemes ?? [];
        return $schemes[0] ?? [
            'primary' => '#3B82F6',
            'secondary' => '#1E40AF',
            'accent' => '#F59E0B',
            'background' => '#FFFFFF',
            'text' => '#1F2937',
            'muted' => '#6B7280',
        ];
    }

    /**
     * Get random color scheme
     */
    public function getRandomColorScheme(): array
    {
        $schemes = $this->color_schemes ?? [];
        if (empty($schemes)) {
            return $this->getDefaultColorScheme();
        }
        return $schemes[array_rand($schemes)];
    }

    /**
     * Get pages from structure
     */
    public function getPages(): array
    {
        return $this->structure['pages'] ?? [];
    }

    /**
     * Get sections for a page
     */
    public function getSections(string $page = 'home'): array
    {
        $pages = $this->getPages();
        return $pages[$page]['sections'] ?? [];
    }

    /**
     * Check if template has blog functionality
     */
    public function hasBlog(): bool
    {
        $pages = $this->getPages();
        return isset($pages['blog']) || in_array($this->type, [self::TYPE_BLOG, self::TYPE_CORPORATE]);
    }

    /**
     * Check if template has news section
     */
    public function hasNews(): bool
    {
        $structure = $this->structure ?? [];
        return $structure['features']['news'] ?? false;
    }

    /**
     * Get prompt for specific section
     */
    public function getPromptForSection(string $section): ?string
    {
        $prompts = $this->default_prompts ?? [];
        return $prompts[$section] ?? null;
    }

    /**
     * Scope by type
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope ordered
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
