<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;

class CreatePrimaryServer extends Command
{
    protected $signature = 'server:create-primary 
                            {ip : Server IP address}
                            {--name=Primary Server : Server name}';

    protected $description = 'Create or update the primary server';

    public function handle(): int
    {
        $ip = $this->argument('ip');
        $name = $this->option('name');

        $server = Server::updateOrCreate(
            ['ip_address' => $ip],
            [
                'name' => $name,
                'ip_address' => $ip,
                'is_primary' => true,
                'is_active' => true,
                'sites_path' => '/var/www/sites',
                'nginx_config_path' => '/etc/nginx/sites-enabled',
                'health_status' => 'unknown',
            ]
        );

        // Ensure only one primary server
        Server::where('id', '!=', $server->id)->update(['is_primary' => false]);

        $this->info("Primary server created/updated: {$server->name} ({$server->ip_address})");

        return Command::SUCCESS;
    }
}
