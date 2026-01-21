<?php

declare(strict_types=1);

namespace App\Services\DNS;

use App\Models\DnsAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class DnspodService implements DnsServiceInterface
{
    // DNSPOD International API
    private const API_BASE = 'https://api.dnspod.com';
    
    // DNSPOD default nameservers
    public const DEFAULT_NAMESERVERS = ['a.dnspod.com', 'c.dnspod.com'];
    
    // Rate limit: 50ms between requests (20 req/sec max)
    private const RATE_LIMIT_DELAY = 50000; // microseconds
    
    // Error codes
    public const ERROR_DOMAIN_EXISTS = '7';
    public const ERROR_DOMAIN_ALIAS = '11';
    public const ERROR_DOMAIN_IN_OTHER_ACCOUNT = '12';
    public const ERROR_DOMAIN_INVALID = '6';
    public const ERROR_DOMAIN_PROHIBITED = '41';
    
    private string $loginToken;

    public function __construct(private DnsAccount $account)
    {
        // DNSPOD uses ID,Token format
        $this->loginToken = $account->api_key;
        if ($account->api_secret) {
            $this->loginToken = $account->api_secret . ',' . $account->api_key;
        }
    }

    /**
     * Make API request with rate limiting
     */
    private function request(string $endpoint, array $data = []): array
    {
        $data = array_merge($data, [
            'login_token' => $this->loginToken,
            'format' => 'json',
            'lang' => 'en',
        ]);

        try {
            $response = Http::asForm()
                ->withHeaders([
                    'User-Agent' => 'SEOPlatform/3.6.2 (support@seoplatform.local)',
                ])
                ->timeout(30)
                ->post(self::API_BASE . $endpoint, $data);

            $result = $response->json();

            // Rate limiting
            usleep(self::RATE_LIMIT_DELAY);

            if (!isset($result['status'])) {
                throw new Exception("DNSPOD API Error: Invalid response");
            }
            
            if ($result['status']['code'] !== '1') {
                $code = $result['status']['code'];
                $message = $result['status']['message'] ?? 'Unknown error';
                
                // Create specific exception for known errors
                throw new DnspodApiException($message, (int) $code);
            }

            return $result;
        } catch (DnspodApiException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('DNSPOD API Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Add domain with detailed error handling
     */
    public function addDomain(string $domain): array
    {
        try {
            $result = $this->request('/Domain.Create', [
                'domain' => $domain,
            ]);

            return [
                'zone_id' => (string) $result['domain']['id'],
                'nameservers' => self::DEFAULT_NAMESERVERS,
                'status' => 'pending', // Always pending until NS are configured
                'success' => true,
            ];
        } catch (DnspodApiException $e) {
            $errorCode = (string) $e->getCode();
            $errorMessages = [
                self::ERROR_DOMAIN_EXISTS => 'Домен уже существует в вашем аккаунте DNSPOD',
                self::ERROR_DOMAIN_ALIAS => 'Домен является алиасом другого домена',
                self::ERROR_DOMAIN_IN_OTHER_ACCOUNT => 'Домен уже добавлен в другой аккаунт DNSPOD. Владелец должен удалить его или передать вам.',
                self::ERROR_DOMAIN_INVALID => 'Некорректный домен',
                self::ERROR_DOMAIN_PROHIBITED => 'Домен нарушает правила DNSPOD',
            ];
            
            $message = $errorMessages[$errorCode] ?? $e->getMessage();
            
            Log::warning('DNSPOD addDomain error', [
                'domain' => $domain,
                'code' => $errorCode,
                'message' => $message,
            ]);
            
            return [
                'zone_id' => null,
                'nameservers' => [],
                'status' => 'error',
                'success' => false,
                'error' => $message,
                'error_code' => $errorCode,
            ];
        } catch (Exception $e) {
            return [
                'zone_id' => null,
                'nameservers' => [],
                'status' => 'error',
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function removeDomain(string $zoneId): bool
    {
        try {
            $this->request('/Domain.Remove', [
                'domain_id' => $zoneId,
            ]);
            return true;
        } catch (Exception $e) {
            Log::warning('Failed to remove domain from DNSPOD', [
                'domain_id' => $zoneId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function createRecord(
        string $zoneId, 
        string $type, 
        string $name, 
        string $content, 
        int $ttl = 600, 
        bool $proxied = false // DNSPOD doesn't support proxy
    ): string {
        $data = [
            'domain_id' => $zoneId,
            'sub_domain' => $name === '@' ? '@' : $name,
            'record_type' => strtoupper($type),
            'record_line' => 'default',
            'value' => $content,
            'ttl' => max(600, $ttl), // DNSPOD minimum TTL is 600
        ];

        $result = $this->request('/Record.Create', $data);

        return (string) $result['record']['id'];
    }

    public function updateRecord(
        string $zoneId, 
        string $recordId, 
        string $type, 
        string $name, 
        string $content, 
        int $ttl = 600, 
        bool $proxied = false
    ): bool {
        $data = [
            'domain_id' => $zoneId,
            'record_id' => $recordId,
            'sub_domain' => $name === '@' ? '@' : $name,
            'record_type' => strtoupper($type),
            'record_line' => 'default',
            'value' => $content,
            'ttl' => max(600, $ttl),
        ];

        try {
            $this->request('/Record.Modify', $data);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function deleteRecord(string $zoneId, string $recordId): bool
    {
        try {
            $this->request('/Record.Remove', [
                'domain_id' => $zoneId,
                'record_id' => $recordId,
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getRecords(string $zoneId): array
    {
        try {
            $result = $this->request('/Record.List', [
                'domain_id' => $zoneId,
            ]);

            return array_map(function ($record) {
                return [
                    'id' => (string) $record['id'],
                    'type' => $record['type'],
                    'name' => $record['name'],
                    'content' => $record['value'],
                    'ttl' => (int) $record['ttl'],
                    'proxied' => false,
                ];
            }, $result['records'] ?? []);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * DNSPOD doesn't provide SSL - handled by Caddy/Let's Encrypt
     */
    public function setupSsl(string $zoneId): bool
    {
        // Return true - SSL will be managed by Caddy
        return true;
    }

    /**
     * Get SSL status - for DNSPOD this is always 'external'
     * Actual SSL status should be checked via CaddyManager
     */
    public function getSslStatus(string $zoneId): string
    {
        return 'external';
    }

    public function verifyConnection(): bool
    {
        try {
            $this->request('/User.Detail');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAccountInfo(): array
    {
        try {
            $result = $this->request('/User.Detail');
            return [
                'id' => $result['info']['user']['id'] ?? null,
                'email' => $result['info']['user']['email'] ?? null,
                'balance' => $result['info']['user']['balance'] ?? null,
                'type' => 'dnspod',
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get all domains
     */
    public function getDomains(int $offset = 0, int $length = 100): array
    {
        $result = $this->request('/Domain.List', [
            'offset' => $offset,
            'length' => $length,
        ]);

        return [
            'zones' => array_map(function ($domain) {
                return [
                    'id' => (string) $domain['id'],
                    'name' => $domain['name'],
                    'status' => $this->mapDomainStatus($domain),
                    'ext_status' => $domain['ext_status'] ?? '',
                    'nameservers' => self::DEFAULT_NAMESERVERS,
                ];
            }, $result['domains'] ?? []),
            'total' => (int) ($result['info']['domain_total'] ?? 0),
        ];
    }

    /**
     * Get domain info with NS activation status
     */
    public function getDomainInfo(string $domainId): array
    {
        $result = $this->request('/Domain.Info', [
            'domain_id' => $domainId,
        ]);

        $domain = $result['domain'];
        
        return [
            'id' => (string) $domain['id'],
            'name' => $domain['name'],
            'status' => $this->mapDomainStatus($domain),
            'ext_status' => $domain['ext_status'] ?? '',
            'grade' => $domain['grade'] ?? 'DP_Free',
            'nameservers' => self::DEFAULT_NAMESERVERS,
            'records_count' => (int) ($domain['records'] ?? 0),
            'is_ns_active' => $this->isNsActiveFromDomain($domain),
        ];
    }

    /**
     * Get detailed zone information (compatibility with CloudflareService)
     */
    public function getZoneDetails(string $zoneId): array
    {
        $info = $this->getDomainInfo($zoneId);
        
        return [
            'id' => $info['id'],
            'name' => $info['name'],
            'status' => $info['is_ns_active'] ? 'active' : 'pending',
            'nameservers' => $info['nameservers'],
            'ext_status' => $info['ext_status'],
        ];
    }

    /**
     * Check if NS records are properly configured from domain data
     * In DNSPOD: ext_status = '' or null means NS are active
     * ext_status = 'notexist' means the domain doesn't exist in global DNS yet
     * ext_status = 'dnserror' means DNS error
     */
    private function isNsActiveFromDomain(array $domain): bool
    {
        $extStatus = $domain['ext_status'] ?? '';
        $status = $domain['status'] ?? 'disable';
        
        // If status is 'enable' and ext_status is empty - NS are active
        return $status === 'enable' && empty($extStatus);
    }

    /**
     * Map DNSPOD domain status to our status
     */
    private function mapDomainStatus(array $domain): string
    {
        $status = $domain['status'] ?? 'disable';
        $extStatus = $domain['ext_status'] ?? '';
        
        if ($status !== 'enable') {
            return 'inactive';
        }
        
        // Check ext_status for NS configuration
        if (!empty($extStatus)) {
            // notexist = NS not pointing to DNSPOD
            // dnserror = DNS error
            return 'pending';
        }
        
        return 'active';
    }

    /**
     * Set domain status
     */
    public function setDomainStatus(string $domainId, bool $enabled): bool
    {
        try {
            $this->request('/Domain.Status', [
                'domain_id' => $domainId,
                'status' => $enabled ? 'enable' : 'disable',
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check domain NS activation status
     * Returns true if NS are properly configured and pointing to DNSPOD
     */
    public function checkNsActivation(string $domainId): array
    {
        try {
            $info = $this->getDomainInfo($domainId);
            
            return [
                'success' => true,
                'is_active' => $info['is_ns_active'],
                'status' => $info['status'],
                'ext_status' => $info['ext_status'],
                'nameservers' => $info['nameservers'],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Trigger NS recheck (DNSPOD doesn't have this, just re-fetch info)
     */
    public function recheckZoneActivation(string $zoneId): bool
    {
        try {
            // DNSPOD doesn't have a specific recheck endpoint
            // Just verify we can get domain info
            $this->getDomainInfo($zoneId);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Custom exception for DNSPOD API errors
 */
class DnspodApiException extends Exception
{
    public function __construct(string $message, int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
