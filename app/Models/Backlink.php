<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Backlink extends BaseModel
{
    protected $fillable = [
        'name',
        'url',
        'anchors',
        'group',
        'description',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'anchors' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Sites relationship (many-to-many)
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'site_backlinks')
            ->withPivot(['anchor', 'placement', 'is_nofollow', 'custom_html'])
            ->withTimestamps();
    }

    /**
     * Get sites count
     */
    public function getSitesCountAttribute(): int
    {
        return $this->sites()->count();
    }

    /**
     * Get random anchor
     */
    public function getRandomAnchor(): ?string
    {
        $anchors = $this->anchors ?? [];
        if (empty($anchors)) {
            return null;
        }
        return $anchors[array_rand($anchors)];
    }

    /**
     * Get anchor by keywords (find matching or random)
     */
    public function getAnchorByKeywords(array $keywords): ?string
    {
        $anchors = $this->anchors ?? [];
        if (empty($anchors)) {
            return null;
        }

        // Try to find matching anchor
        foreach ($anchors as $anchor) {
            foreach ($keywords as $keyword) {
                if (stripos($anchor, $keyword) !== false || stripos($keyword, $anchor) !== false) {
                    return $anchor;
                }
            }
        }

        // Return random if no match
        return $this->getRandomAnchor();
    }

    /**
     * Add anchor
     */
    public function addAnchor(string $anchor): void
    {
        $anchors = $this->anchors ?? [];
        if (!in_array($anchor, $anchors)) {
            $anchors[] = $anchor;
            $this->anchors = $anchors;
            $this->save();
        }
    }

    /**
     * Remove anchor
     */
    public function removeAnchor(string $anchor): void
    {
        $anchors = $this->anchors ?? [];
        $anchors = array_values(array_filter($anchors, fn($a) => $a !== $anchor));
        $this->anchors = $anchors;
        $this->save();
    }

    /**
     * Get HTML link
     */
    public function getHtmlLink(?string $anchor = null, bool $nofollow = false): string
    {
        $anchor = $anchor ?? $this->getRandomAnchor() ?? $this->url;
        $rel = $nofollow ? ' rel="nofollow"' : '';
        return sprintf('<a href="%s"%s>%s</a>', 
            htmlspecialchars($this->url), 
            $rel, 
            htmlspecialchars($anchor)
        );
    }

    /**
     * Scope by group
     */
    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * Scope ordered
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority', 'desc')->orderBy('name');
    }

    /**
     * Get unique groups
     */
    public static function getGroups(): array
    {
        return static::whereNotNull('group')
            ->distinct()
            ->pluck('group')
            ->toArray();
    }
}
