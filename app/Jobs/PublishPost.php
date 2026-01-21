<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Post;
use App\Services\Site\SiteBuilder;
use App\Services\Site\SiteDeployer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(public Post $post)
    {
    }

    public function handle(SiteBuilder $builder, SiteDeployer $deployer): void
    {
        Log::info('Publishing post', [
            'post_id' => $this->post->id,
            'site_id' => $this->post->site_id,
        ]);

        try {
            $site = $this->post->site;

            // Build post page
            $postHtml = $builder->buildPostPage($this->post);
            
            // Deploy post file
            $deployer->deployPost($site, $this->post, $postHtml);

            // Update sitemap
            $deployer->updateSitemap($site);

            // Mark as published
            $this->post->publish();

            Log::info('Post published successfully', [
                'post_id' => $this->post->id,
                'url' => $this->post->url,
            ]);

        } catch (\Exception $e) {
            Log::error('Post publication failed', [
                'post_id' => $this->post->id,
                'error' => $e->getMessage(),
            ]);

            $this->post->update([
                'status' => Post::STATUS_ERROR,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
