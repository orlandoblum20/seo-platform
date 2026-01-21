<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Site;
use App\Services\Site\SiteDeployer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UnpublishSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(public Site $site)
    {
    }

    public function handle(SiteDeployer $deployer): void
    {
        Log::info('Starting site unpublication', [
            'site_id' => $this->site->id,
            'domain' => $this->site->domain->domain,
        ]);

        try {
            // Remove from web server
            $deployer->undeploy($this->site);

            // Update status
            $this->site->update([
                'status' => Site::STATUS_UNPUBLISHED,
                'unpublished_at' => now(),
            ]);

            Log::info('Site unpublished successfully', ['site_id' => $this->site->id]);

        } catch (\Exception $e) {
            Log::error('Site unpublication failed', [
                'site_id' => $this->site->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
