<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Site;
use App\Services\Content\ContentGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSiteContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(public Site $site)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(ContentGenerator $generator): void
    {
        Log::info('Starting content generation', ['site_id' => $this->site->id]);

        try {
            $this->site->update([
                'status' => Site::STATUS_GENERATING,
                'generation_started_at' => now(),
                'error_message' => null,
            ]);

            // Generate content
            $content = $generator->generateSiteContent($this->site);

            // Update site with generated content
            $this->site->update([
                'content' => $content,
                'status' => Site::STATUS_GENERATED,
                'generation_completed_at' => now(),
            ]);

            Log::info('Content generation completed', ['site_id' => $this->site->id]);

        } catch (\Exception $e) {
            Log::error('Content generation failed', [
                'site_id' => $this->site->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->site->update([
                'status' => Site::STATUS_ERROR,
                'error_message' => 'Ошибка генерации: ' . $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Content generation job failed permanently', [
            'site_id' => $this->site->id,
            'error' => $exception->getMessage(),
        ]);

        $this->site->update([
            'status' => Site::STATUS_ERROR,
            'error_message' => 'Генерация не удалась после нескольких попыток: ' . $exception->getMessage(),
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Wait 30s, 1m, 2m between retries
    }
}
