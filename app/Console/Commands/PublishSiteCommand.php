<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Jobs\PublishSite;
use Illuminate\Console\Command;

class PublishSiteCommand extends Command
{
    protected $signature = 'site:publish 
                            {site : Site ID or domain}
                            {--sync : Run synchronously}';

    protected $description = 'Publish a site to the server';

    public function handle(): int
    {
        $identifier = $this->argument('site');

        $site = is_numeric($identifier)
            ? Site::find($identifier)
            : Site::whereHas('domain', fn($q) => $q->where('domain', $identifier))->first();

        if (!$site) {
            $this->error("Site not found: {$identifier}");
            return self::FAILURE;
        }

        if (!$site->canBePublished()) {
            $this->error("Site cannot be published. Status: {$site->status}");
            return self::FAILURE;
        }

        $this->info("Publishing site: {$site->title}");
        $this->info("Domain: {$site->domain->domain}");

        if ($this->option('sync')) {
            try {
                $builder = app(\App\Services\Site\SiteBuilder::class);
                $deployer = app(\App\Services\Site\SiteDeployer::class);

                $this->info("Building site...");
                $buildPath = $builder->build($site);

                $this->info("Deploying to server...");
                $deployer->deploy($site, $buildPath);

                $this->info("Configuring web server...");
                $deployer->configureWebServer($site);

                $site->update([
                    'status' => Site::STATUS_PUBLISHED,
                    'published_at' => now(),
                ]);

                $this->info("Site published successfully!");
                $this->info("URL: https://{$site->domain->domain}");

            } catch (\Exception $e) {
                $this->error("Publication failed: {$e->getMessage()}");
                return self::FAILURE;
            }
        } else {
            PublishSite::dispatch($site);
            $this->info("Job dispatched to queue");
        }

        return self::SUCCESS;
    }
}
