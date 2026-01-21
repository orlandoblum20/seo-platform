<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AutopostSetting;
use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAutopost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function handle(): void
    {
        Log::info('Processing autopost queue');

        // Get all settings due for posting
        $dueSettings = AutopostSetting::dueForPosting()
            ->with('site')
            ->get();

        $processed = 0;

        foreach ($dueSettings as $settings) {
            $site = $settings->site;

            // Skip if site is not published
            if (!$site || !$site->isPublished()) {
                Log::warning('Skipping autopost - site not published', [
                    'site_id' => $settings->site_id,
                ]);
                $settings->scheduleNextPost();
                continue;
            }

            // Dispatch post generation
            $postType = $settings->getRandomPostType();
            
            GeneratePost::dispatch($site, $postType, true);

            Log::info('Autopost dispatched', [
                'site_id' => $site->id,
                'type' => $postType,
            ]);

            $processed++;
        }

        Log::info('Autopost processing completed', ['processed' => $processed]);
    }
}
