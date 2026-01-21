<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AutopostSetting extends BaseModel
{
    protected $fillable = [
        'site_id',
        'is_enabled',
        'frequency',
        'frequency_variance',
        'post_types',
        'time_range_start',
        'time_range_end',
        'weekdays_only',
        'custom_prompts',
        'last_post_at',
        'next_post_at',
        'posts_count',
        'errors_count',
        'last_error',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'weekdays_only' => 'boolean',
        'post_types' => 'array',
        'custom_prompts' => 'array',
        'last_post_at' => 'datetime',
        'next_post_at' => 'datetime',
        'posts_count' => 'integer',
        'errors_count' => 'integer',
    ];

    /**
     * Frequency constants
     */
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_EVERY_2_DAYS = 'every_2_days';
    public const FREQUENCY_EVERY_3_DAYS = 'every_3_days';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_BIWEEKLY = 'biweekly';
    public const FREQUENCY_RANDOM = 'random';

    /**
     * Get frequencies
     */
    public static function getFrequencies(): array
    {
        return [
            self::FREQUENCY_DAILY => 'Ежедневно',
            self::FREQUENCY_EVERY_2_DAYS => 'Раз в 2 дня',
            self::FREQUENCY_EVERY_3_DAYS => 'Раз в 3 дня',
            self::FREQUENCY_WEEKLY => 'Раз в неделю',
            self::FREQUENCY_BIWEEKLY => 'Раз в 2 недели',
            self::FREQUENCY_RANDOM => 'Случайный интервал',
        ];
    }

    /**
     * Site relationship
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get days between posts for frequency
     */
    public function getDaysInterval(): int
    {
        return match ($this->frequency) {
            self::FREQUENCY_DAILY => 1,
            self::FREQUENCY_EVERY_2_DAYS => 2,
            self::FREQUENCY_EVERY_3_DAYS => 3,
            self::FREQUENCY_WEEKLY => 7,
            self::FREQUENCY_BIWEEKLY => 14,
            self::FREQUENCY_RANDOM => rand(2, 7),
            default => 3,
        };
    }

    /**
     * Calculate next post time
     */
    public function calculateNextPostTime(): Carbon
    {
        $days = $this->getDaysInterval();
        
        // Add variance if set (makes timing less predictable)
        if ($this->frequency_variance > 0) {
            $variance = rand(0, $this->frequency_variance);
            $days += (rand(0, 1) ? $variance : -$variance);
            $days = max(1, $days);
        }

        $nextDate = now()->addDays($days);

        // Skip weekends if weekdays_only
        if ($this->weekdays_only) {
            while ($nextDate->isWeekend()) {
                $nextDate->addDay();
            }
        }

        // Set random time within range
        $startHour = $this->time_range_start 
            ? (int) explode(':', $this->time_range_start)[0] 
            : 9;
        $endHour = $this->time_range_end 
            ? (int) explode(':', $this->time_range_end)[0] 
            : 18;

        $hour = rand($startHour, $endHour);
        $minute = rand(0, 59);

        return $nextDate->setTime($hour, $minute);
    }

    /**
     * Schedule next post
     */
    public function scheduleNextPost(): void
    {
        $this->update([
            'next_post_at' => $this->calculateNextPostTime(),
        ]);
    }

    /**
     * Record successful post
     */
    public function recordSuccess(): void
    {
        $this->increment('posts_count');
        $this->update([
            'last_post_at' => now(),
            'last_error' => null,
        ]);
        $this->scheduleNextPost();
    }

    /**
     * Record error
     */
    public function recordError(string $error): void
    {
        $this->increment('errors_count');
        $this->update([
            'last_error' => $error,
        ]);
        // Reschedule anyway, but maybe with delay
        $this->scheduleNextPost();
    }

    /**
     * Get random post type
     */
    public function getRandomPostType(): string
    {
        $types = $this->post_types ?? [Post::TYPE_ARTICLE];
        return $types[array_rand($types)];
    }

    /**
     * Scope enabled
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope due for posting
     */
    public function scopeDueForPosting(Builder $query): Builder
    {
        return $query->where('is_enabled', true)
            ->where(function ($q) {
                $q->whereNull('next_post_at')
                    ->orWhere('next_post_at', '<=', now());
            });
    }
}
