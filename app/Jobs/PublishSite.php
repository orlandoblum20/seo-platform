<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Site;
use App\Models\GlobalSetting;
use App\Services\Site\SiteBuilder;
use App\Services\Site\SiteDeployer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180; // 3 minutes

    public function __construct(public Site $site)
    {
    }

    public function handle(SiteBuilder $builder, SiteDeployer $deployer): void
    {
        Log::info('Starting site publication', [
            'site_id' => $this->site->id,
            'domain' => $this->site->domain->domain,
        ]);

        try {
            $this->site->update([
                'status' => Site::STATUS_PUBLISHING,
                'error_message' => null,
            ]);

            // Step 1: Build static files
            $buildPath = $builder->build($this->site);

            Log::info('Site built successfully', [
                'site_id' => $this->site->id,
                'build_path' => $buildPath,
            ]);

            // Step 2: Deploy to server
            $deployer->deploy($this->site, $buildPath);

            Log::info('Site deployed successfully', [
                'site_id' => $this->site->id,
            ]);

            // Step 3: Configure Nginx/Caddy
            $deployer->configureWebServer($this->site);

            // Step 4: Update site status
            $this->site->update([
                'status' => Site::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

            Log::info('Site publication completed', [
                'site_id' => $this->site->id,
                'url' => $this->site->url,
            ]);

        } catch (\Exception $e) {
            Log::error('Site publication failed', [
                'site_id' => $this->site->id,
                'error' => $e->getMessage(),
            ]);

            $this->site->update([
                'status' => Site::STATUS_ERROR,
                'error_message' => 'Ошибка публикации: ' . $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->site->update([
            'status' => Site::STATUS_ERROR,
            'error_message' => 'Публикация не удалась: ' . $exception->getMessage(),
        ]);
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }
}
