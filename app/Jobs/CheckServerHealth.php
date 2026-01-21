<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckServerHealth implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 30;

    public function __construct(public Server $server)
    {
    }

    public function handle(): void
    {
        Log::info('Checking server health', ['server' => $this->server->name]);

        try {
            // Check via HTTP ping
            $response = Http::timeout(10)->get("http://{$this->server->ip_address}/health");
            
            $status = $response->successful() ? 'ok' : 'warning';
            
            $this->server->update([
                'health_status' => $status,
                'last_health_check' => now(),
            ]);

        } catch (\Exception $e) {
            Log::warning('Server health check failed', [
                'server' => $this->server->name,
                'error' => $e->getMessage(),
            ]);

            $this->server->update([
                'health_status' => 'error',
                'last_health_check' => now(),
            ]);
        }
    }
}
