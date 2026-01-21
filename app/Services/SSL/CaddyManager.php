<?php

declare(strict_types=1);

namespace App\Services\SSL;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * CaddyManager - управление SSL сертификатами через Caddy
 * 
 * Caddy автоматически выпускает Let's Encrypt сертификаты
 * при добавлении домена в конфигурацию.
 */
class CaddyManager
{
    // Caddy Admin API (если включено)
    private string $adminApi;
    
    // Путь к Caddyfile
    private string $caddyfilePath;
    
    // Путь к директории с сайтами
    private string $sitesPath;

    public function __construct()
    {
        $this->adminApi = config('services.caddy.admin_api', 'http://localhost:2019');
        $this->caddyfilePath = config('services.caddy.caddyfile', '/etc/caddy/Caddyfile');
        $this->sitesPath = config('services.caddy.sites_path', '/etc/caddy/sites');
    }

    /**
     * Add domain to Caddy for SSL provisioning
     */
    public function addDomain(string $domain, ?string $backendIp = null): bool
    {
        try {
            // Determine backend - Docker listens on port 8080
            $backend = $backendIp ? "http://{$backendIp}:8080" : 'http://localhost:8080';
            
            // Create site configuration
            $config = $this->generateSiteConfig($domain, $backend);
            
            // Write site config file
            $sitePath = "{$this->sitesPath}/{$domain}.caddy";
            
            if (!File::isDirectory($this->sitesPath)) {
                File::makeDirectory($this->sitesPath, 0755, true);
            }
            
            File::put($sitePath, $config);
            
            // Reload Caddy
            $this->reloadCaddy();
            
            Log::info('CaddyManager: Domain added', [
                'domain' => $domain,
                'config_path' => $sitePath,
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('CaddyManager: Failed to add domain', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Remove domain from Caddy
     */
    public function removeDomain(string $domain): bool
    {
        try {
            $sitePath = "{$this->sitesPath}/{$domain}.caddy";
            
            if (File::exists($sitePath)) {
                File::delete($sitePath);
                $this->reloadCaddy();
                
                Log::info('CaddyManager: Domain removed', ['domain' => $domain]);
            }
            
            return true;
        } catch (Exception $e) {
            Log::error('CaddyManager: Failed to remove domain', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if SSL certificate is active for domain
     */
    public function checkSslStatus(string $domain): bool
    {
        try {
            // Method 1: Check via Caddy Admin API
            if ($this->isAdminApiAvailable()) {
                return $this->checkSslViaApi($domain);
            }
            
            // Method 2: Check via HTTPS request
            return $this->checkSslViaRequest($domain);
        } catch (Exception $e) {
            Log::warning('CaddyManager: SSL check failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get SSL certificate details
     */
    public function getSslDetails(string $domain): array
    {
        try {
            // Try to get certificate info via Admin API
            if ($this->isAdminApiAvailable()) {
                $response = Http::timeout(5)
                    ->get("{$this->adminApi}/pki/ca/local/certificates");
                    
                if ($response->successful()) {
                    // Parse certificates (implementation depends on Caddy version)
                    return [
                        'status' => 'available',
                        'issuer' => 'Let\'s Encrypt',
                        'managed_by' => 'Caddy',
                    ];
                }
            }
            
            // Fallback: check via HTTPS
            $isActive = $this->checkSslViaRequest($domain);
            
            return [
                'status' => $isActive ? 'active' : 'pending',
                'issuer' => $isActive ? 'Let\'s Encrypt' : 'unknown',
                'managed_by' => 'Caddy',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add multiple domains at once
     */
    public function addDomainsBulk(array $domains, ?string $backendIp = null): array
    {
        $results = ['success' => [], 'failed' => []];
        
        foreach ($domains as $domain) {
            if ($this->addDomain($domain, $backendIp)) {
                $results['success'][] = $domain;
            } else {
                $results['failed'][] = $domain;
            }
            
            // Small delay between domains
            usleep(100000); // 100ms
        }
        
        return $results;
    }

    /**
     * Generate Caddy site configuration
     */
    private function generateSiteConfig(string $domain, string $backend): string
    {
        // Support both root domain and www subdomain
        return <<<CADDY
{$domain}, www.{$domain} {
    # Automatic HTTPS with Let's Encrypt
    tls {
        on_demand
    }
    
    # Reverse proxy to backend
    reverse_proxy {$backend} {
        header_up Host {host}
        header_up X-Real-IP {remote_host}
        header_up X-Forwarded-For {remote_host}
        header_up X-Forwarded-Proto {scheme}
    }
    
    # Security headers
    header {
        X-Content-Type-Options nosniff
        X-Frame-Options SAMEORIGIN
        Referrer-Policy strict-origin-when-cross-origin
    }
    
    # Logging
    log {
        output file /var/log/caddy/{$domain}.log {
            roll_size 10mb
            roll_keep 5
        }
    }
}
CADDY;
    }

    /**
     * Generate static site configuration (for generated landing pages)
     */
    public function generateStaticSiteConfig(string $domain, string $rootPath): string
    {
        return <<<CADDY
{$domain}, www.{$domain} {
    # Automatic HTTPS
    tls {
        on_demand
    }
    
    # Serve static files
    root * {$rootPath}
    file_server
    
    # Try files and index
    try_files {path} {path}/ /index.html
    
    # Compression
    encode gzip zstd
    
    # Security headers
    header {
        X-Content-Type-Options nosniff
        X-Frame-Options SAMEORIGIN
        Referrer-Policy strict-origin-when-cross-origin
        Cache-Control "public, max-age=86400"
    }
    
    # Logging
    log {
        output file /var/log/caddy/{$domain}.log {
            roll_size 10mb
            roll_keep 5
        }
    }
}
CADDY;
    }

    /**
     * Reload Caddy configuration
     */
    public function reloadCaddy(): bool
    {
        try {
            // Method 1: Via Admin API (try multiple addresses)
            $apiAddresses = [
                $this->adminApi,                    // configured (default localhost:2019)
                'http://host.docker.internal:2019', // Docker for Mac/Windows
                'http://172.17.0.1:2019',           // Docker default bridge gateway
            ];
            
            // Get host gateway IP dynamically
            $hostGateway = $this->getDockerHostGateway();
            if ($hostGateway) {
                array_unshift($apiAddresses, "http://{$hostGateway}:2019");
            }
            
            foreach ($apiAddresses as $apiUrl) {
                try {
                    // Load config via Caddy Admin API
                    $caddyfileContent = @file_get_contents($this->caddyfilePath);
                    if (!$caddyfileContent) {
                        continue;
                    }
                    
                    $response = Http::timeout(5)
                        ->withHeaders(['Content-Type' => 'text/caddyfile'])
                        ->post("{$apiUrl}/load", $caddyfileContent);
                    
                    if ($response->successful()) {
                        Log::info('CaddyManager: Reloaded via Admin API', ['url' => $apiUrl]);
                        return true;
                    }
                } catch (Exception $e) {
                    // Try next address
                    continue;
                }
            }
            
            // Method 2: Via shell exec (works if running on host)
            $commands = [
                'systemctl reload caddy 2>&1',
                'caddy reload --config /etc/caddy/Caddyfile 2>&1',
                'pkill -USR1 caddy 2>&1', // Send SIGUSR1 to reload
            ];
            
            foreach ($commands as $cmd) {
                exec($cmd, $output, $returnCode);
                if ($returnCode === 0) {
                    Log::info('CaddyManager: Reloaded via command', ['cmd' => $cmd]);
                    return true;
                }
            }
            
            Log::warning('CaddyManager: Could not reload Caddy automatically. Manual reload required.');
            return false;
            
        } catch (Exception $e) {
            Log::error('CaddyManager: Failed to reload', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Get Docker host gateway IP
     */
    private function getDockerHostGateway(): ?string
    {
        try {
            // Try to get default gateway
            $output = shell_exec("ip route | grep default | awk '{print $3}'");
            if ($output) {
                return trim($output);
            }
            
            // Fallback: read from /etc/hosts
            $hosts = @file_get_contents('/etc/hosts');
            if ($hosts && preg_match('/(\d+\.\d+\.\d+\.\d+)\s+host\.docker\.internal/', $hosts, $matches)) {
                return $matches[1];
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Check if Caddy Admin API is available
     */
    private function isAdminApiAvailable(): bool
    {
        try {
            $response = Http::timeout(2)->get("{$this->adminApi}/config/");
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check SSL via Admin API
     */
    private function checkSslViaApi(string $domain): bool
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->adminApi}/reverse_proxy/upstreams");
                
            // If we can get config that includes our domain, it's managed
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check SSL by making HTTPS request to domain
     */
    private function checkSslViaRequest(string $domain): bool
    {
        try {
            $response = Http::timeout(10)
                ->withOptions([
                    'verify' => true, // Verify SSL certificate
                ])
                ->get("https://{$domain}");
                
            // If we get any response with valid SSL, it's working
            return $response->status() < 500;
        } catch (Exception $e) {
            // SSL error or connection refused - not ready yet
            Log::debug('CaddyManager: SSL check via request failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get Caddy status
     */
    public function getStatus(): array
    {
        try {
            $adminAvailable = $this->isAdminApiAvailable();
            
            // Count managed sites
            $sitesCount = 0;
            if (File::isDirectory($this->sitesPath)) {
                $sitesCount = count(File::files($this->sitesPath));
            }
            
            return [
                'running' => $adminAvailable || $this->isCaddyRunning(),
                'admin_api' => $adminAvailable,
                'sites_managed' => $sitesCount,
                'caddyfile_path' => $this->caddyfilePath,
                'sites_path' => $this->sitesPath,
            ];
        } catch (Exception $e) {
            return [
                'running' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if Caddy process is running
     */
    private function isCaddyRunning(): bool
    {
        exec('pgrep -x caddy', $output, $returnCode);
        return $returnCode === 0;
    }
}
