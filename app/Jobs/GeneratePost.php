<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Site;
use App\Models\Post;
use App\Services\Content\ContentGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GeneratePost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(
        public Site $site,
        public string $type = 'article',
        public bool $schedule = true
    ) {
    }

    public function handle(ContentGenerator $generator): void
    {
        Log::info('Starting post generation', [
            'site_id' => $this->site->id,
            'type' => $this->type,
        ]);

        try {
            // Generate post content
            $postData = $generator->generateBlogPost($this->site, $this->type);

            // Determine scheduled time
            $scheduledAt = null;
            if ($this->schedule) {
                $autopostSettings = $this->site->autopostSettings;
                if ($autopostSettings) {
                    $scheduledAt = $autopostSettings->calculateNextPostTime();
                } else {
                    // Default: random time in next 24-72 hours during work hours
                    $scheduledAt = now()
                        ->addHours(rand(24, 72))
                        ->setTime(rand(9, 17), rand(0, 59));
                }
            }

            // Create post
            $post = Post::create([
                'site_id' => $this->site->id,
                'type' => $this->type,
                'title' => $postData['title'],
                'slug' => $postData['slug'] ?? Str::slug($postData['title']),
                'excerpt' => $postData['excerpt'] ?? null,
                'content' => $postData['content'],
                'seo_title' => $postData['seo_title'] ?? null,
                'seo_description' => $postData['seo_description'] ?? null,
                'status' => $scheduledAt ? Post::STATUS_SCHEDULED : Post::STATUS_DRAFT,
                'scheduled_at' => $scheduledAt,
            ]);

            Log::info('Post generated successfully', [
                'post_id' => $post->id,
                'site_id' => $this->site->id,
                'scheduled_at' => $scheduledAt,
            ]);

            // Update autopost settings if applicable
            if ($this->site->autopostSettings && $this->schedule) {
                $this->site->autopostSettings->recordSuccess();
            }

        } catch (\Exception $e) {
            Log::error('Post generation failed', [
                'site_id' => $this->site->id,
                'error' => $e->getMessage(),
            ]);

            if ($this->site->autopostSettings) {
                $this->site->autopostSettings->recordError($e->getMessage());
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Post generation job failed', [
            'site_id' => $this->site->id,
            'error' => $exception->getMessage(),
        ]);

        if ($this->site->autopostSettings) {
            $this->site->autopostSettings->recordError($exception->getMessage());
        }
    }

    public function backoff(): array
    {
        return [60, 180, 300];
    }
}
