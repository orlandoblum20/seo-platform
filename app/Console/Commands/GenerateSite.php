<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Jobs\GenerateSiteContent;
use Illuminate\Console\Command;

class GenerateSite extends Command
{
    protected $signature = 'site:generate 
                            {site : Site ID or domain}
                            {--sync : Run synchronously instead of queue}';

    protected $description = 'Generate content for a site';

    public function handle(): int
    {
        $identifier = $this->argument('site');

        // Find site
        $site = is_numeric($identifier)
            ? Site::find($identifier)
            : Site::whereHas('domain', fn($q) => $q->where('domain', $identifier))->first();

        if (!$site) {
            $this->error("Site not found: {$identifier}");
            return self::FAILURE;
        }

        $this->info("Starting content generation for: {$site->title}");
        $this->info("Domain: {$site->domain->domain}");

        if ($this->option('sync')) {
            $this->info("Running synchronously...");
            
            try {
                $generator = app(\App\Services\Content\ContentGenerator::class);
                $content = $generator->generateSiteContent($site);
                
                $site->update([
                    'content' => $content,
                    'status' => Site::STATUS_GENERATED,
                    'generation_completed_at' => now(),
                ]);
                
                $this->info("Content generated successfully!");
                
            } catch (\Exception $e) {
                $this->error("Generation failed: {$e->getMessage()}");
                return self::FAILURE;
            }
        } else {
            GenerateSiteContent::dispatch($site);
            $this->info("Job dispatched to queue");
        }

        return self::SUCCESS;
    }
}
