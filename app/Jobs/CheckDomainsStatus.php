<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Domain;
use App\Models\DnsAccount;
use App\Services\DNS\DnsManager;
use App\Services\SSL\CaddyManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckDomainsStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300; // 5 minutes max

    public function __construct()
    {
    }

    public function handle(DnsManager $dnsManager): void
    {
        Log::info('CheckDomainsStatus: Starting periodic domain status check');

        // Get domains that need status check
        // 1. dns_configuring - waiting for NS activation
        // 2. ssl_pending - waiting for SSL certificate
        $domains = Domain::whereIn('status', [
            Domain::STATUS_DNS_CONFIGURING,
            Domain::STATUS_SSL_PENDING,
        ])
        ->where(function ($query) {
            $query->whereNotNull('cloudflare_zone_id')
                  ->orWhereNotNull('dnspod_domain_id');
        })
        ->whereHas('dnsAccount', function ($query) {
            $query->where('is_active', true);
        })
        ->orderBy('last_check_at', 'asc') // Check oldest first
        ->limit(50) // Process max 50 domains per run
        ->get();

        if ($domains->isEmpty()) {
            Log::info('CheckDomainsStatus: No domains need checking');
            return;
        }

        Log::info('CheckDomainsStatus: Found ' . $domains->count() . ' domains to check');

        $stats = [
            'checked' => 0,
            'activated' => 0,
            'ssl_ready' => 0,
            'errors' => 0,
        ];

        foreach ($domains as $domain) {
            try {
                if ($domain->dnsAccount->isCloudflare()) {
                    $result = $this->checkCloudfareDomain($domain, $dnsManager);
                } else {
                    $result = $this->checkDnspodDomain($domain, $dnsManager);
                }
                
                $stats['checked']++;
                
                if ($result['activated']) {
                    $stats['activated']++;
                }
                if ($result['ssl_ready']) {
                    $stats['ssl_ready']++;
                }

                // Rate limiting - 300ms between requests
                usleep(300000);

            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('CheckDomainsStatus: Error checking domain', [
                    'domain' => $domain->domain,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('CheckDomainsStatus: Completed', $stats);
    }

    /**
     * Check Cloudflare domain status
     */
    private function checkCloudfareDomain(Domain $domain, DnsManager $dnsManager): array
    {
        $result = [
            'activated' => false,
            'ssl_ready' => false,
        ];

        $service = $dnsManager->getService($domain->dnsAccount);
        $zoneId = $domain->cloudflare_zone_id;

        if (!$zoneId) {
            return $result;
        }

        // Get zone details from Cloudflare
        $zoneDetails = $service->getZoneDetails($zoneId);
        $zoneStatus = $zoneDetails['status'] ?? 'unknown';

        Log::debug('CheckDomainsStatus: Checking Cloudflare domain', [
            'domain' => $domain->domain,
            'current_status' => $domain->status,
            'zone_status' => $zoneStatus,
        ]);

        // Domain is in dns_configuring - check if NS are active
        if ($domain->status === Domain::STATUS_DNS_CONFIGURING) {
            if ($zoneStatus === 'active') {
                // NS are configured! Setup SSL and move to ssl_pending
                Log::info('CheckDomainsStatus: Cloudflare domain activated, setting up SSL', [
                    'domain' => $domain->domain,
                ]);

                // Setup SSL (Full mode + Universal SSL)
                $service->setupSsl($zoneId);
                
                $domain->update([
                    'status' => Domain::STATUS_SSL_PENDING,
                    'ssl_status' => Domain::SSL_PENDING,
                    'nameservers' => $zoneDetails['nameservers'] ?? $domain->nameservers,
                    'last_check_at' => now(),
                    'error_message' => null,
                ]);

                $result['activated'] = true;

                // Check SSL immediately - might already be ready
                $sslStatus = $service->getSslStatus($zoneId);
                if ($sslStatus === 'active') {
                    $domain->update([
                        'status' => Domain::STATUS_ACTIVE,
                        'ssl_status' => Domain::SSL_ACTIVE,
                    ]);
                    $result['ssl_ready'] = true;
                }
            } else {
                // Still waiting for NS
                $domain->update([
                    'last_check_at' => now(),
                    'nameservers' => $zoneDetails['nameservers'] ?? $domain->nameservers,
                ]);
            }
        }

        // Domain is in ssl_pending - check if SSL is ready
        elseif ($domain->status === Domain::STATUS_SSL_PENDING) {
            $sslStatus = $service->getSslStatus($zoneId);
            
            if ($sslStatus === 'active') {
                $domain->update([
                    'status' => Domain::STATUS_ACTIVE,
                    'ssl_status' => Domain::SSL_ACTIVE,
                    'last_check_at' => now(),
                    'error_message' => null,
                ]);
                $result['ssl_ready'] = true;

                Log::info('CheckDomainsStatus: Cloudflare SSL ready', [
                    'domain' => $domain->domain,
                ]);
            } else {
                $domain->update([
                    'last_check_at' => now(),
                    'ssl_status' => $sslStatus === 'pending' ? Domain::SSL_PENDING : $domain->ssl_status,
                ]);
            }
        }

        return $result;
    }

    /**
     * Check DNSPOD domain status
     */
    private function checkDnspodDomain(Domain $domain, DnsManager $dnsManager): array
    {
        $result = [
            'activated' => false,
            'ssl_ready' => false,
        ];

        $service = $dnsManager->getService($domain->dnsAccount);
        $domainId = $domain->dnspod_domain_id;

        if (!$domainId) {
            return $result;
        }

        // Get domain details from DNSPOD
        $domainInfo = $service->getZoneDetails($domainId);
        $zoneStatus = $domainInfo['status'] ?? 'unknown';
        $extStatus = $domainInfo['ext_status'] ?? '';

        Log::debug('CheckDomainsStatus: Checking DNSPOD domain', [
            'domain' => $domain->domain,
            'current_status' => $domain->status,
            'zone_status' => $zoneStatus,
            'ext_status' => $extStatus,
        ]);

        // Domain is in dns_configuring - check if NS are active
        if ($domain->status === Domain::STATUS_DNS_CONFIGURING) {
            // DNSPOD: status='active' and ext_status='' means NS are configured
            if ($zoneStatus === 'active') {
                Log::info('CheckDomainsStatus: DNSPOD domain activated', [
                    'domain' => $domain->domain,
                ]);

                // For DNSPOD, SSL is handled by Caddy
                // Move to ssl_pending and let Caddy handle SSL
                $domain->update([
                    'status' => Domain::STATUS_SSL_PENDING,
                    'ssl_status' => Domain::SSL_PENDING,
                    'last_check_at' => now(),
                    'error_message' => null,
                ]);

                $result['activated'] = true;

                // Try to setup SSL via Caddy
                $this->setupCaddySsl($domain);
            } else {
                // Still waiting for NS
                $domain->update([
                    'last_check_at' => now(),
                ]);
            }
        }

        // Domain is in ssl_pending - check if SSL is ready via Caddy
        elseif ($domain->status === Domain::STATUS_SSL_PENDING) {
            $sslReady = $this->checkCaddySsl($domain);
            
            if ($sslReady) {
                $domain->update([
                    'status' => Domain::STATUS_ACTIVE,
                    'ssl_status' => Domain::SSL_ACTIVE,
                    'last_check_at' => now(),
                    'error_message' => null,
                ]);
                $result['ssl_ready'] = true;

                Log::info('CheckDomainsStatus: DNSPOD SSL ready (Caddy)', [
                    'domain' => $domain->domain,
                ]);
            } else {
                $domain->update([
                    'last_check_at' => now(),
                ]);
            }
        }

        return $result;
    }

    /**
     * Setup SSL via Caddy for DNSPOD domain
     */
    private function setupCaddySsl(Domain $domain): bool
    {
        try {
            $caddyManager = app(CaddyManager::class);
            return $caddyManager->addDomain($domain->domain, $domain->server?->ip_address);
        } catch (\Exception $e) {
            Log::warning('CheckDomainsStatus: Failed to setup Caddy SSL', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check SSL status via Caddy
     */
    private function checkCaddySsl(Domain $domain): bool
    {
        try {
            $caddyManager = app(CaddyManager::class);
            return $caddyManager->checkSslStatus($domain->domain);
        } catch (\Exception $e) {
            Log::warning('CheckDomainsStatus: Failed to check Caddy SSL', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
