<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessScheduledPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function handle(): void
    {
        Log::info('Processing scheduled posts');

        $posts = Post::readyToPublish()->get();

        $published = 0;

        foreach ($posts as $post) {
            // Check if site is still published
            if (!$post->site || !$post->site->isPublished()) {
                Log::warning('Skipping scheduled post - site not published', [
                    'post_id' => $post->id,
                ]);
                continue;
            }

            // Dispatch publication
            PublishPost::dispatch($post);
            $published++;

            Log::info('Scheduled post queued for publication', [
                'post_id' => $post->id,
                'site_id' => $post->site_id,
            ]);
        }

        Log::info('Scheduled posts processing completed', ['published' => $published]);
    }
}
