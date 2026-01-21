<?php

declare(strict_types=1);

namespace App\Services\DNS;

use App\Models\DnsAccount;
use App\Models\Domain;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Exception;

class DnsManager
{
    /**
     * Get DNS service for account
     */
    public function getService(DnsAccount $account): DnsServiceInterface
    {
        return match ($account->provider) {
            DnsAccount::PROVIDER_CLOUDFLARE => new CloudflareService($account),
            DnsAccount::PROVIDER_DNSPOD => new DnspodService($account),
            default => throw new Exception("Unknown DNS provider: {$account->provider}"),
        };
    }

    /**
     * Add domains in bulk
     * 
     * @param array $domains Array of domain names
     * @param DnsAccount $account DNS account to use
     * @param Server|null $server Server to point domains to
     * @return array Results with success/failure for each domain
     */
    public function addDomainsBulk(array $domains, DnsAccount $account, ?Server $server = null): array
    {
        $service = $this->getService($account);
        $serverIp = $server?->ip_address ?? Server::primary()?->ip_address;

        if (!$serverIp) {
            throw new Exception('No server IP available');
        }

        $results = [
            'success' => [],
            'failed' => [],
        ];

        $processedCount = 0;

        foreach ($domains as $domainName) {
            $domainName = trim(strtolower($domainName));
            
            if (empty($domainName)) {
                continue;
            }

            // Check if domain already exists
            if (Domain::where('domain', $domainName)->exists()) {
                $results['failed'][] = [
                    'domain' => $domainName,
                    'error' => 'Domain already exists in system',
                ];
                continue;
            }

            // Rate limiting: delay between API requests to avoid Cloudflare ban
            // Cloudflare allows 1200 requests per 5 minutes, but we add 500ms delay to be safe
            if ($processedCount > 0) {
                usleep(500000); // 500ms delay
            }

            try {
                // Add domain to DNS provider
                $dnsResult = $service->addDomain($domainName);

                // Check if DNSPOD returned an error (returns array with success=false)
                if (isset($dnsResult['success']) && $dnsResult['success'] === false) {
                    $results['failed'][] = [
                        'domain' => $domainName,
                        'error' => $dnsResult['error'] ?? 'Unknown error',
                        'error_code' => $dnsResult['error_code'] ?? null,
                    ];
                    
                    Log::warning('Domain add failed (provider error)', [
                        'domain' => $domainName,
                        'error' => $dnsResult['error'] ?? 'Unknown',
                        'error_code' => $dnsResult['error_code'] ?? null,
                    ]);
                    
                    continue;
                }

                // Create domain record - always start with DNS_CONFIGURING
                // because NS records need to be configured at registrar first
                $domain = Domain::create([
                    'domain' => $domainName,
                    'dns_account_id' => $account->id,
                    'server_id' => $server?->id ?? Server::primary()?->id,
                    'status' => Domain::STATUS_DNS_CONFIGURING,
                    'ssl_status' => Domain::SSL_NONE,
                    'cloudflare_zone_id' => $account->isCloudflare() ? $dnsResult['zone_id'] : null,
                    'dnspod_domain_id' => $account->isDnspod() ? $dnsResult['zone_id'] : null,
                    'nameservers' => $dnsResult['nameservers'] ?? ($account->isDnspod() ? DnspodService::DEFAULT_NAMESERVERS : []),
                ]);

                // Create A record pointing to server
                $recordId = $service->createRecord(
                    $dnsResult['zone_id'],
                    'A',
                    '@',
                    $serverIp,
                    $account->isDnspod() ? 600 : 300, // DNSPOD min TTL is 600
                    $account->isCloudflare() // Proxy only for Cloudflare
                );

                // Create www record
                $service->createRecord(
                    $dnsResult['zone_id'],
                    'A',
                    'www',
                    $serverIp,
                    $account->isDnspod() ? 600 : 300,
                    $account->isCloudflare()
                );

                // Setup SSL for Cloudflare (but keep status as DNS_CONFIGURING)
                // SSL will only become active after NS are verified
                if ($account->isCloudflare()) {
                    $service->setupSsl($dnsResult['zone_id']);
                    // Status remains DNS_CONFIGURING until NS are verified
                    // After NS verification, status will change to SSL_PENDING, then ACTIVE
                }
                // For DNSPOD, SSL will be handled by Caddy after NS activation

                $results['success'][] = [
                    'domain' => $domainName,
                    'domain_id' => $domain->id,
                    'zone_id' => $dnsResult['zone_id'],
                    'nameservers' => $dnsResult['nameservers'] ?? ($account->isDnspod() ? DnspodService::DEFAULT_NAMESERVERS : []),
                ];

                $processedCount++;

                Log::info('Domain added successfully', [
                    'domain' => $domainName,
                    'account' => $account->name,
                    'provider' => $account->provider,
                    'nameservers' => $dnsResult['nameservers'] ?? [],
                ]);

            } catch (Exception $e) {
                $results['failed'][] = [
                    'domain' => $domainName,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to add domain', [
                    'domain' => $domainName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update account sync time
        $account->update(['last_sync_at' => now()]);

        return $results;
    }

    /**
     * Remove domain from system and DNS
     */
    public function removeDomain(Domain $domain): bool
    {
        if (!$domain->dnsAccount) {
            $domain->delete();
            return true;
        }

        try {
            $service = $this->getService($domain->dnsAccount);
            $zoneId = $domain->cloudflare_zone_id ?? $domain->dnspod_domain_id;

            if ($zoneId) {
                $service->removeDomain($zoneId);
            }

            $domain->delete();
            return true;

        } catch (Exception $e) {
            Log::error('Failed to remove domain from DNS', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);
            
            // Delete from system anyway
            $domain->delete();
            return false;
        }
    }

    /**
     * Update IP for multiple domains
     */
    public function updateIpBulk(array $domainIds, string $newIp): array
    {
        $results = ['success' => [], 'failed' => []];

        $domains = Domain::whereIn('id', $domainIds)->with('dnsAccount')->get();

        foreach ($domains as $domain) {
            try {
                if (!$domain->dnsAccount) {
                    throw new Exception('No DNS account associated');
                }

                $service = $this->getService($domain->dnsAccount);
                $zoneId = $domain->cloudflare_zone_id ?? $domain->dnspod_domain_id;

                if (!$zoneId) {
                    throw new Exception('No zone ID found');
                }

                // Get current records
                $records = $service->getRecords($zoneId);

                // Update A records
                foreach ($records as $record) {
                    if ($record['type'] === 'A') {
                        $service->updateRecord(
                            $zoneId,
                            $record['id'],
                            'A',
                            $record['name'],
                            $newIp,
                            300,
                            $domain->dnsAccount->isCloudflare()
                        );
                    }
                }

                $results['success'][] = $domain->domain;

            } catch (Exception $e) {
                $results['failed'][] = [
                    'domain' => $domain->domain,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Check SSL status for domain
     */
    public function checkSslStatus(Domain $domain): string
    {
        if (!$domain->dnsAccount) {
            return Domain::SSL_NONE;
        }

        try {
            $service = $this->getService($domain->dnsAccount);
            $zoneId = $domain->cloudflare_zone_id ?? $domain->dnspod_domain_id;

            if (!$zoneId) {
                return Domain::SSL_NONE;
            }

            $status = $service->getSslStatus($zoneId);

            // Map service status to domain status
            $sslStatus = match ($status) {
                'active' => Domain::SSL_ACTIVE,
                'pending' => Domain::SSL_PENDING,
                'error' => Domain::SSL_ERROR,
                'external' => Domain::SSL_PENDING, // DNSPOD - check Caddy
                default => Domain::SSL_NONE,
            };

            // Update domain
            $domain->update([
                'ssl_status' => $sslStatus,
                'last_check_at' => now(),
            ]);

            // If SSL is active and domain was waiting, mark as active
            if ($sslStatus === Domain::SSL_ACTIVE && $domain->status === Domain::STATUS_SSL_PENDING) {
                $domain->update(['status' => Domain::STATUS_ACTIVE]);
            }

            return $sslStatus;

        } catch (Exception $e) {
            Log::error('Failed to check SSL status', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);
            return Domain::SSL_ERROR;
        }
    }

    /**
     * Verify DNS account connection
     */
    public function verifyAccount(DnsAccount $account): bool
    {
        try {
            $service = $this->getService($account);
            return $service->verifyConnection();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Recheck domain status (trigger NS verification at provider)
     */
    public function recheckDomainStatus(Domain $domain): array
    {
        if (!$domain->dnsAccount) {
            return ['success' => false, 'error' => 'No DNS account'];
        }

        try {
            $service = $this->getService($domain->dnsAccount);
            $zoneId = $domain->cloudflare_zone_id ?? $domain->dnspod_domain_id;

            if (!$zoneId) {
                return ['success' => false, 'error' => 'No zone ID'];
            }

            // For Cloudflare
            if ($domain->dnsAccount->isCloudflare()) {
                return $this->recheckCloudflareStatus($domain, $service, $zoneId);
            }

            // For DNSPOD
            if ($domain->dnsAccount->isDnspod()) {
                return $this->recheckDnspodStatus($domain, $service, $zoneId);
            }

            return ['success' => false, 'error' => 'Unknown DNS provider'];

        } catch (Exception $e) {
            Log::error('Failed to recheck domain status', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Recheck Cloudflare domain status
     */
    private function recheckCloudflareStatus(Domain $domain, $service, string $zoneId): array
    {
        $service->recheckZoneActivation($zoneId);
        $details = $service->getZoneDetails($zoneId);
        
        // Map Cloudflare status to our status
        $newStatus = $domain->status;
        $newSslStatus = $domain->ssl_status;

        if ($details['status'] === 'active') {
            // Zone is active - NS are configured correctly
            if ($domain->ssl_status === Domain::SSL_ACTIVE) {
                $newStatus = Domain::STATUS_ACTIVE;
            } else {
                // Zone active but SSL not ready yet
                $newStatus = Domain::STATUS_SSL_PENDING;
                $newSslStatus = Domain::SSL_PENDING;
                
                // Setup SSL if not yet done
                $service->setupSsl($zoneId);
            }
        } elseif (in_array($details['status'], ['pending', 'initializing'])) {
            // Still waiting for NS configuration
            $newStatus = Domain::STATUS_DNS_CONFIGURING;
        } else {
            // Error or other status
            $newStatus = Domain::STATUS_ERROR;
        }

        $domain->update([
            'status' => $newStatus,
            'ssl_status' => $newSslStatus,
            'nameservers' => $details['nameservers'],
            'last_check_at' => now(),
            'error_message' => $newStatus === Domain::STATUS_ERROR ? "Zone status: {$details['status']}" : null,
        ]);

        return [
            'success' => true,
            'provider' => 'cloudflare',
            'provider_status' => $details['status'],
            'domain_status' => $newStatus,
            'ssl_status' => $newSslStatus,
            'nameservers' => $details['nameservers'],
            'domain' => $domain->fresh(),
        ];
    }

    /**
     * Recheck DNSPOD domain status
     */
    private function recheckDnspodStatus(Domain $domain, $service, string $zoneId): array
    {
        $details = $service->getZoneDetails($zoneId);
        
        // DNSPOD status mapping:
        // status='active' + ext_status='' -> NS configured correctly
        // status='pending' or ext_status='notexist' -> waiting for NS
        $providerStatus = $details['status'] ?? 'unknown';
        $extStatus = $details['ext_status'] ?? '';
        
        $newStatus = $domain->status;
        $newSslStatus = $domain->ssl_status;

        if ($providerStatus === 'active') {
            // NS are configured correctly
            // For DNSPOD, SSL is handled by Caddy
            if ($domain->ssl_status === Domain::SSL_ACTIVE) {
                $newStatus = Domain::STATUS_ACTIVE;
            } else {
                // NS active, now wait for Caddy SSL
                $newStatus = Domain::STATUS_SSL_PENDING;
                $newSslStatus = Domain::SSL_PENDING;
                
                // Try to setup SSL via Caddy
                try {
                    $caddyManager = app(\App\Services\SSL\CaddyManager::class);
                    $caddyManager->addDomain($domain->domain, $domain->server?->ip_address);
                } catch (Exception $e) {
                    Log::warning('Failed to setup Caddy SSL during recheck', [
                        'domain' => $domain->domain,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } elseif ($providerStatus === 'pending' || !empty($extStatus)) {
            // Still waiting for NS configuration
            $newStatus = Domain::STATUS_DNS_CONFIGURING;
        } else {
            // Inactive or other status
            $newStatus = Domain::STATUS_ERROR;
        }

        $domain->update([
            'status' => $newStatus,
            'ssl_status' => $newSslStatus,
            'nameservers' => DnspodService::DEFAULT_NAMESERVERS,
            'last_check_at' => now(),
            'error_message' => $newStatus === Domain::STATUS_ERROR ? "DNSPOD status: {$providerStatus}, ext: {$extStatus}" : null,
        ]);

        return [
            'success' => true,
            'provider' => 'dnspod',
            'provider_status' => $providerStatus,
            'ext_status' => $extStatus,
            'domain_status' => $newStatus,
            'ssl_status' => $newSslStatus,
            'nameservers' => DnspodService::DEFAULT_NAMESERVERS,
            'domain' => $domain->fresh(),
        ];
    }

    /**
     * Bulk recheck domain status
     */
    public function recheckDomainStatusBulk(array $domainIds): array
    {
        $results = ['success' => [], 'failed' => []];

        $domains = Domain::whereIn('id', $domainIds)->with('dnsAccount')->get();

        $processedCount = 0;

        foreach ($domains as $domain) {
            // Rate limiting: delay between API requests
            if ($processedCount > 0) {
                usleep(300000); // 300ms delay
            }

            $result = $this->recheckDomainStatus($domain);
            
            if ($result['success']) {
                $results['success'][] = [
                    'domain' => $domain->domain,
                    'status' => $result['status'] ?? 'checked',
                    'domain_status' => $result['domain_status'] ?? $domain->status,
                ];
            } else {
                $results['failed'][] = [
                    'domain' => $domain->domain,
                    'error' => $result['error'] ?? 'Unknown error',
                ];
            }

            $processedCount++;
        }

        return $results;
    }
}
