<?php

declare(strict_types=1);

namespace App\Services\SSL;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * CaddyManager - управление SSL сертификатами через Caddy + Let's Encrypt
 * 
 * Используется для доменов на DNSPOD и других провайдерах,
 * где нет встроенного SSL (в отличие от Cloudflare).
 */
class CaddyManager
{
    private string $adminApi;
    private string $sitesPath;
    private string $caddyfilePath;

    public function __construct()
    {
        $this->adminApi = env('CADDY_ADMIN_API', 'http://caddy:2019');
        $this->sitesPath = '/etc/caddy/sites';
        $this->caddyfilePath = '/etc/caddy/Caddyfile';
    }

    /**
     * Add domain to Caddy for automatic SSL provisioning
     */
    public function addDomain(string $domain, ?string $backendIp = null): bool
    {
        try {
            $config = $this->generateSiteConfig($domain);

            // Ensure directory exists and is writable
            if (!File::isDirectory($this->sitesPath)) {
                File::makeDirectory($this->sitesPath, 0755, true);
            }

            $sitePath = "{$this->sitesPath}/{$domain}.caddy";
            File::put($sitePath, $config);

            // Reload Caddy to apply new config
            $this->reloadCaddy();

            Log::info('CaddyManager: Domain added for SSL', [
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
            }

            $this->reloadCaddy();

            Log::info('CaddyManager: Domain removed', ['domain' => $domain]);
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
        $result = $this->checkSsl($domain);
        return $result['valid'] ?? false;
    }

    /**
     * Check SSL certificate details
     */
    public function checkSsl(string $domain): array
    {
        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);
            
            $socket = @stream_socket_client(
                "ssl://{$domain}:443",
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($socket) {
                $params = stream_context_get_params($socket);
                $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate'] ?? '');
                fclose($socket);

                if ($cert) {
                    return [
                        'valid' => true,
                        'status' => 'active',
                        'issuer' => $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'Unknown',
                        'expires' => date('Y-m-d', $cert['validTo_time_t'] ?? 0),
                        'subject' => $cert['subject']['CN'] ?? $domain,
                    ];
                }
            }

            return [
                'valid' => false,
                'status' => 'pending',
                'error' => $errstr ?: 'Connection failed',
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get comprehensive SSL details for domain
     */
    public function getSslDetails(string $domain): array
    {
        $sslInfo = $this->checkSsl($domain);
        $isConfigured = File::exists("{$this->sitesPath}/{$domain}.caddy");
        
        return [
            'configured' => $isConfigured,
            'valid' => $sslInfo['valid'] ?? false,
            'status' => $sslInfo['status'] ?? ($isConfigured ? 'pending' : 'none'),
            'issuer' => $sslInfo['issuer'] ?? null,
            'expires' => $sslInfo['expires'] ?? null,
            'subject' => $sslInfo['subject'] ?? null,
            'error' => $sslInfo['error'] ?? null,
            'provider' => 'caddy',
        ];
    }

    /**
     * Generate Caddy site configuration for domain
     */
    private function generateSiteConfig(string $domain): string
    {
        // Caddy will automatically:
        // 1. Obtain SSL certificate from Let's Encrypt
        // 2. Handle certificate renewal
        // 3. Redirect HTTP to HTTPS
        return <<<CADDY
{$domain} {
    reverse_proxy http://nginx:80
    encode gzip
    
    header {
        X-Content-Type-Options nosniff
        X-Frame-Options SAMEORIGIN
        Referrer-Policy strict-origin-when-cross-origin
    }
}

www.{$domain} {
    redir https://{$domain}{uri} permanent
}
CADDY;
    }

    /**
     * Reload Caddy configuration
     */
    private function reloadCaddy(): bool
    {
        try {
            // Read main Caddyfile
            $mainConfig = '';
            if (File::exists($this->caddyfilePath)) {
                $mainConfig = File::get($this->caddyfilePath);
            }
            
            // Append all site configs
            $siteConfigs = '';
            if (File::isDirectory($this->sitesPath)) {
                $files = File::glob("{$this->sitesPath}/*.caddy");
                foreach ($files as $file) {
                    $siteConfigs .= "\n" . File::get($file);
                }
            }
            
            $fullConfig = $mainConfig . $siteConfigs;

            // Send to Caddy Admin API with correct content type
            $response = Http::timeout(15)
                ->withHeaders(['Content-Type' => 'text/caddyfile'])
                ->withBody($fullConfig, 'text/caddyfile')
                ->post("{$this->adminApi}/load");

            if ($response->successful()) {
                Log::info('CaddyManager: Configuration reloaded successfully');
                return true;
            }

            Log::warning('CaddyManager: Reload returned non-success', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 200),
            ]);
            
            // Config is written to file, will apply on container restart
            return true;
        } catch (Exception $e) {
            Log::warning('CaddyManager: Failed to reload via API, config saved to file', [
                'error' => $e->getMessage(),
            ]);
            // Not fatal - config file is written and will apply on restart
            return true;
        }
    }

    /**
     * Check if Caddy service is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->adminApi}/config/");
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get all domains configured in Caddy
     */
    public function getDomains(): array
    {
        $domains = [];
        
        if (File::isDirectory($this->sitesPath)) {
            $files = File::glob("{$this->sitesPath}/*.caddy");
            foreach ($files as $file) {
                $domains[] = basename($file, '.caddy');
            }
        }
        
        return $domains;
    }

    /**
     * Force certificate renewal for domain
     */
    public function renewCertificate(string $domain): bool
    {
        // Remove and re-add domain to trigger new certificate
        $this->removeDomain($domain);
        sleep(1);
        return $this->addDomain($domain);
    }
}
