<?php

declare(strict_types=1);

namespace App\Services\Site;

use App\Models\Site;
use App\Models\Post;
use App\Models\Server;
use App\Models\GlobalSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Exception;

class SiteDeployer
{
    /**
     * Deploy site to server
     */
    public function deploy(Site $site, string $buildPath): void
    {
        $server = $site->domain->server ?? Server::primary();
        
        if (!$server) {
            throw new Exception('No server available for deployment');
        }

        $domain = $site->domain->domain;
        $remotePath = "{$server->sites_path}/{$domain}";

        // For now, use local file operations
        // In production, this would use SSH/SFTP
        if ($this->isLocalServer($server)) {
            $this->deployLocal($buildPath, $remotePath);
        } else {
            $this->deployRemote($server, $buildPath, $remotePath);
        }

        Log::info('Site deployed', [
            'site_id' => $site->id,
            'domain' => $domain,
            'server' => $server->name,
        ]);
    }

    /**
     * Deploy to local server
     */
    private function deployLocal(string $buildPath, string $remotePath): void
    {
        // Create directory if not exists
        if (!File::exists($remotePath)) {
            File::makeDirectory($remotePath, 0755, true);
        }

        // Sync files
        File::copyDirectory($buildPath, $remotePath);

        // Set permissions
        $this->setPermissions($remotePath);
    }

    /**
     * Deploy to remote server via SSH
     */
    private function deployRemote(Server $server, string $buildPath, string $remotePath): void
    {
        // Create temp archive
        $archivePath = storage_path('app/temp/' . uniqid('deploy_') . '.tar.gz');
        File::makeDirectory(dirname($archivePath), 0755, true);

        // Create archive
        $this->createArchive($buildPath, $archivePath);

        try {
            // Connect via SSH
            $connection = $this->connectSsh($server);

            // Create remote directory
            $this->sshExec($connection, "mkdir -p {$remotePath}");

            // Upload archive
            $this->scpUpload($connection, $archivePath, "/tmp/deploy.tar.gz");

            // Extract archive
            $this->sshExec($connection, "tar -xzf /tmp/deploy.tar.gz -C {$remotePath} --strip-components=1");

            // Cleanup
            $this->sshExec($connection, "rm /tmp/deploy.tar.gz");

            // Set permissions
            $this->sshExec($connection, "chmod -R 755 {$remotePath}");
            $this->sshExec($connection, "chown -R www-data:www-data {$remotePath}");

            // Close connection
            $this->disconnectSsh($connection);

        } finally {
            // Remove local archive
            if (File::exists($archivePath)) {
                File::delete($archivePath);
            }
        }
    }

    /**
     * Deploy a single post
     */
    public function deployPost(Site $site, Post $post, string $postHtml): void
    {
        $server = $site->domain->server ?? Server::primary();
        $domain = $site->domain->domain;
        $remotePath = "{$server->sites_path}/{$domain}";

        $postDir = match ($post->type) {
            Post::TYPE_NEWS => 'news',
            Post::TYPE_FAQ => 'faq',
            default => 'blog',
        };

        $postPath = "{$remotePath}/{$postDir}";

        if ($this->isLocalServer($server)) {
            if (!File::exists($postPath)) {
                File::makeDirectory($postPath, 0755, true);
            }
            File::put("{$postPath}/{$post->slug}.html", $postHtml);
        } else {
            $connection = $this->connectSsh($server);
            $this->sshExec($connection, "mkdir -p {$postPath}");
            
            // Write to temp file and upload
            $tempFile = storage_path('app/temp/' . uniqid('post_') . '.html');
            File::put($tempFile, $postHtml);
            $this->scpUpload($connection, $tempFile, "{$postPath}/{$post->slug}.html");
            File::delete($tempFile);
            
            $this->disconnectSsh($connection);
        }
    }

    /**
     * Update sitemap after new post
     */
    public function updateSitemap(Site $site): void
    {
        $builder = new SiteBuilder();
        $buildPath = storage_path("app/sites/builds/{$site->domain->domain}");
        
        // Rebuild sitemap
        // For simplicity, we rebuild the whole site sitemap
        // In production, could just append to existing
        
        $server = $site->domain->server ?? Server::primary();
        $remotePath = "{$server->sites_path}/{$site->domain->domain}";

        if ($this->isLocalServer($server)) {
            // Sitemap will be regenerated on next full deploy
            // For now, just log
            Log::info('Sitemap update pending', ['site_id' => $site->id]);
        }
    }

    /**
     * Configure web server (Nginx)
     */
    public function configureWebServer(Site $site): void
    {
        $server = $site->domain->server ?? Server::primary();
        $domain = $site->domain->domain;

        $nginxConfig = $this->generateNginxConfig($site, $server);

        $configPath = "{$server->nginx_config_path}/{$domain}.conf";

        if ($this->isLocalServer($server)) {
            File::put($configPath, $nginxConfig);
            
            // Test and reload nginx
            exec('nginx -t 2>&1', $output, $returnCode);
            if ($returnCode === 0) {
                exec('systemctl reload nginx');
            } else {
                Log::error('Nginx config test failed', [
                    'domain' => $domain,
                    'output' => implode("\n", $output),
                ]);
                throw new Exception('Nginx configuration error');
            }
        } else {
            $connection = $this->connectSsh($server);
            
            // Write config
            $tempFile = storage_path('app/temp/' . uniqid('nginx_') . '.conf');
            File::put($tempFile, $nginxConfig);
            $this->scpUpload($connection, $tempFile, $configPath);
            File::delete($tempFile);
            
            // Test and reload
            $result = $this->sshExec($connection, 'nginx -t 2>&1');
            if (strpos($result, 'successful') !== false) {
                $this->sshExec($connection, 'systemctl reload nginx');
            } else {
                throw new Exception('Nginx configuration error on remote server');
            }
            
            $this->disconnectSsh($connection);
        }

        Log::info('Nginx configured', ['domain' => $domain]);
    }

    /**
     * Generate Nginx config for site
     */
    private function generateNginxConfig(Site $site, Server $server): string
    {
        $domain = $site->domain->domain;
        $rootPath = "{$server->sites_path}/{$domain}";
        $keitaroEnabled = $site->keitaro_enabled && GlobalSetting::get('keitaro_enabled', false);

        $phpInject = '';
        if ($keitaroEnabled) {
            $phpInject = '
    # Keitaro TDS Integration
    location ~ \.html$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root/keitaro.php;
        include fastcgi_params;
        fastcgi_param ORIGINAL_URI $uri;
    }';
        }

        return <<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$domain} www.{$domain};

    root {$rootPath};
    index index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml;

    # Cache static assets
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
{$phpInject}

    location / {
        try_files \$uri \$uri/ \$uri.html =404;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    error_page 404 /404.html;
    error_page 500 502 503 504 /50x.html;
}
NGINX;
    }

    /**
     * Undeploy site (remove from server)
     */
    public function undeploy(Site $site): void
    {
        $server = $site->domain->server ?? Server::primary();
        $domain = $site->domain->domain;

        if ($this->isLocalServer($server)) {
            // Remove site files
            $sitePath = "{$server->sites_path}/{$domain}";
            if (File::exists($sitePath)) {
                File::deleteDirectory($sitePath);
            }

            // Remove nginx config
            $configPath = "{$server->nginx_config_path}/{$domain}.conf";
            if (File::exists($configPath)) {
                File::delete($configPath);
                exec('systemctl reload nginx');
            }
        } else {
            $connection = $this->connectSsh($server);
            
            $this->sshExec($connection, "rm -rf {$server->sites_path}/{$domain}");
            $this->sshExec($connection, "rm -f {$server->nginx_config_path}/{$domain}.conf");
            $this->sshExec($connection, "systemctl reload nginx");
            
            $this->disconnectSsh($connection);
        }

        Log::info('Site undeployed', ['site_id' => $site->id, 'domain' => $domain]);
    }

    /**
     * Check if server is local
     */
    private function isLocalServer(Server $server): bool
    {
        $localIps = ['127.0.0.1', 'localhost', '::1'];
        return in_array($server->ip_address, $localIps) || 
               $server->ip_address === gethostbyname(gethostname());
    }

    /**
     * Set file permissions
     */
    private function setPermissions(string $path): void
    {
        // Set directory permissions
        $directories = File::directories($path);
        foreach ($directories as $dir) {
            chmod($dir, 0755);
            $this->setPermissions($dir);
        }

        // Set file permissions
        $files = File::files($path);
        foreach ($files as $file) {
            chmod($file, 0644);
        }
    }

    /**
     * Create tar.gz archive
     */
    private function createArchive(string $sourcePath, string $archivePath): void
    {
        $phar = new \PharData($archivePath);
        $phar->buildFromDirectory($sourcePath);
        $phar->compress(\Phar::GZ);
    }

    // SSH helper methods (simplified - in production use phpseclib or similar)
    
    private function connectSsh(Server $server): mixed
    {
        // Placeholder - implement with phpseclib
        return null;
    }

    private function disconnectSsh(mixed $connection): void
    {
        // Placeholder
    }

    private function sshExec(mixed $connection, string $command): string
    {
        // Placeholder - for now use local exec
        exec($command, $output, $returnCode);
        return implode("\n", $output);
    }

    private function scpUpload(mixed $connection, string $localPath, string $remotePath): void
    {
        // Placeholder
        if (File::exists($localPath)) {
            File::copy($localPath, $remotePath);
        }
    }
}
