<?php

declare(strict_types=1);

namespace App\Services\DNS;

use App\Models\DnsAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CloudflareService implements DnsServiceInterface
{
    private const API_BASE = 'https://api.cloudflare.com/client/v4';

    // Коды ошибок которые считаем успехом
    private const IGNORABLE_ERRORS = [
        81058, // "An identical record already exists" - DNS запись уже есть
    ];

    private string $apiKey;
    private ?string $email;
    private ?string $accountId;

    public function __construct(private DnsAccount $account)
    {
        $this->apiKey = $account->api_key;
        $this->email = $account->email;
        $this->accountId = $account->account_id;
    }

    /**
     * Make API request
     */
    private function request(string $method, string $endpoint, array $data = [], bool $throwOnError = true): array
    {
        $headers = [
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ];

        // If using Global API Key instead of API Token
        if ($this->email) {
            $headers = [
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ];
        }

        $url = self::API_BASE . $endpoint;

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->$method($url, $data);

            $result = $response->json();

            if (!$response->successful() || !($result['success'] ?? false)) {
                $errors = $result['errors'] ?? [['message' => 'Unknown error']];
                
                if ($throwOnError) {
                    throw new Exception('Cloudflare API Error: ' . json_encode($errors));
                }
                
                return [
                    'success' => false,
                    'errors' => $errors,
                    'result' => $result['result'] ?? null,
                ];
            }

            return $result;
        } catch (Exception $e) {
            Log::error('Cloudflare API Error', [
                'endpoint' => $endpoint,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if error code is ignorable (should be treated as success)
     */
    private function isIgnorableError(array $errors): bool
    {
        foreach ($errors as $error) {
            if (in_array($error['code'] ?? 0, self::IGNORABLE_ERRORS)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get error code from errors array
     */
    private function getErrorCode(array $errors): ?int
    {
        return $errors[0]['code'] ?? null;
    }

    public function addDomain(string $domain): array
    {
        $data = [
            'name' => $domain,
            'jump_start' => true,
        ];

        // Note: Don't pass account.id - Cloudflare auto-determines the account
        // Passing wrong account_id causes "Permission denied" error

        $result = $this->request('post', '/zones', $data);

        return [
            'zone_id' => $result['result']['id'],
            'nameservers' => $result['result']['name_servers'] ?? [],
            'status' => $result['result']['status'],
        ];
    }

    public function removeDomain(string $zoneId): bool
    {
        try {
            $this->request('delete', "/zones/{$zoneId}");
            return true;
        } catch (Exception $e) {
            Log::warning('Failed to remove domain from Cloudflare', [
                'zone_id' => $zoneId,
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
        int $ttl = 300,
        bool $proxied = true
    ): string {
        $data = [
            'type' => strtoupper($type),
            'name' => $name,
            'content' => $content,
            'ttl' => $proxied ? 1 : $ttl, // TTL 1 = automatic when proxied
            'proxied' => $proxied && in_array(strtoupper($type), ['A', 'AAAA', 'CNAME']),
        ];

        // Делаем запрос без выброса исключения
        $result = $this->request('post', "/zones/{$zoneId}/dns_records", $data, false);

        // Успешный запрос
        if ($result['success'] ?? false) {
            return $result['result']['id'];
        }

        $errors = $result['errors'] ?? [];
        $errorCode = $this->getErrorCode($errors);

        // Код 81058 - запись уже существует, находим её и возвращаем ID
        if ($errorCode === 81058) {
            Log::info('DNS record already exists, finding existing record', [
                'zone_id' => $zoneId,
                'type' => $type,
                'name' => $name,
            ]);

            // Ищем существующую запись
            $existingRecord = $this->findRecord($zoneId, $type, $name);
            
            if ($existingRecord) {
                // Обновляем контент если отличается
                if ($existingRecord['content'] !== $content) {
                    $this->updateRecord($zoneId, $existingRecord['id'], $type, $name, $content, $ttl, $proxied);
                }
                return $existingRecord['id'];
            }

            // Запись должна существовать, возвращаем placeholder ID
            return 'existing_' . md5($zoneId . $type . $name);
        }

        // Другие ошибки - выбрасываем исключение
        throw new Exception('Cloudflare API Error: ' . json_encode($errors));
    }

    /**
     * Find existing DNS record
     */
    public function findRecord(string $zoneId, string $type, string $name): ?array
    {
        try {
            $records = $this->getRecords($zoneId);
            
            foreach ($records as $record) {
                if ($record['type'] === strtoupper($type) && $record['name'] === $name) {
                    return $record;
                }
            }
            
            return null;
        } catch (Exception $e) {
            Log::warning('Failed to find record', [
                'zone_id' => $zoneId,
                'type' => $type,
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function updateRecord(
        string $zoneId,
        string $recordId,
        string $type,
        string $name,
        string $content,
        int $ttl = 300,
        bool $proxied = true
    ): bool {
        $data = [
            'type' => strtoupper($type),
            'name' => $name,
            'content' => $content,
            'ttl' => $proxied ? 1 : $ttl,
            'proxied' => $proxied && in_array(strtoupper($type), ['A', 'AAAA', 'CNAME']),
        ];

        try {
            $this->request('put', "/zones/{$zoneId}/dns_records/{$recordId}", $data);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function deleteRecord(string $zoneId, string $recordId): bool
    {
        try {
            $this->request('delete', "/zones/{$zoneId}/dns_records/{$recordId}");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getRecords(string $zoneId): array
    {
        $result = $this->request('get', "/zones/{$zoneId}/dns_records", [
            'per_page' => 1000,
        ]);

        return array_map(function ($record) {
            return [
                'id' => $record['id'],
                'type' => $record['type'],
                'name' => $record['name'],
                'content' => $record['content'],
                'ttl' => $record['ttl'],
                'proxied' => $record['proxied'] ?? false,
            ];
        }, $result['result'] ?? []);
    }

    public function setupSsl(string $zoneId): bool
    {
        try {
            // Set SSL mode to Full
            $this->request('patch', "/zones/{$zoneId}/settings/ssl", [
                'value' => 'full',
            ]);

            // Enable Always Use HTTPS
            $this->request('patch', "/zones/{$zoneId}/settings/always_use_https", [
                'value' => 'on',
            ]);

            // Enable Automatic HTTPS Rewrites
            $this->request('patch', "/zones/{$zoneId}/settings/automatic_https_rewrites", [
                'value' => 'on',
            ]);

            // Enable Universal SSL (Edge Certificates)
            $this->enableUniversalSsl($zoneId);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to setup SSL', [
                'zone_id' => $zoneId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Enable Universal SSL (Edge Certificates)
     */
    public function enableUniversalSsl(string $zoneId): bool
    {
        try {
            $this->request('patch', "/zones/{$zoneId}/ssl/universal/settings", [
                'enabled' => true,
            ]);
            return true;
        } catch (Exception $e) {
            Log::warning('Failed to enable Universal SSL', [
                'zone_id' => $zoneId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get SSL mode setting
     */
    public function getSslMode(string $zoneId): string
    {
        try {
            $result = $this->request('get', "/zones/{$zoneId}/settings/ssl");
            return $result['result']['value'] ?? 'off';
        } catch (Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Get Edge Certificates (Universal SSL) status
     */
    public function getEdgeCertificates(string $zoneId): array
    {
        try {
            $result = $this->request('get', "/zones/{$zoneId}/ssl/certificate_packs?status=all");
            $certs = $result['result'] ?? [];

            $activeCerts = [];
            $pendingCerts = [];

            foreach ($certs as $cert) {
                $certInfo = [
                    'id' => $cert['id'] ?? null,
                    'type' => $cert['type'] ?? 'unknown',
                    'status' => $cert['status'] ?? 'unknown',
                    'hosts' => $cert['hosts'] ?? [],
                ];

                if ($cert['status'] === 'active') {
                    $activeCerts[] = $certInfo;
                } else {
                    $pendingCerts[] = $certInfo;
                }
            }

            return [
                'has_active' => !empty($activeCerts),
                'active' => $activeCerts,
                'pending' => $pendingCerts,
                'total' => count($certs),
            ];
        } catch (Exception $e) {
            Log::warning('Failed to get edge certificates', [
                'zone_id' => $zoneId,
                'error' => $e->getMessage(),
            ]);
            return [
                'has_active' => false,
                'active' => [],
                'pending' => [],
                'total' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get comprehensive SSL status
     */
    public function getSslStatus(string $zoneId): string
    {
        try {
            // First check SSL mode
            $sslMode = $this->getSslMode($zoneId);

            if ($sslMode === 'off') {
                return 'none';
            }

            // Check certificate status
            $certs = $this->getEdgeCertificates($zoneId);

            if ($certs['has_active']) {
                return 'active';
            }

            if (!empty($certs['pending'])) {
                return 'pending';
            }

            // SSL mode is set but no certs yet - still pending
            return 'pending';
        } catch (Exception $e) {
            return 'error';
        }
    }

    /**
     * Get full SSL details for a zone
     */
    public function getSslDetails(string $zoneId): array
    {
        try {
            $mode = $this->getSslMode($zoneId);
            $certs = $this->getEdgeCertificates($zoneId);

            return [
                'mode' => $mode,
                'certificates' => $certs,
                'status' => $certs['has_active'] ? 'active' : ($mode !== 'off' ? 'pending' : 'none'),
            ];
        } catch (Exception $e) {
            return [
                'mode' => 'unknown',
                'certificates' => [],
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function verifyConnection(): bool
    {
        try {
            $this->request('get', '/user/tokens/verify');
            return true;
        } catch (Exception $e) {
            // Try with zones endpoint for Global API Key
            try {
                $this->request('get', '/zones', ['per_page' => 1]);
                return true;
            } catch (Exception $e2) {
                return false;
            }
        }
    }

    public function getAccountInfo(): array
    {
        try {
            // Try token verification first
            $result = $this->request('get', '/user/tokens/verify');
            return [
                'status' => $result['result']['status'] ?? 'unknown',
                'type' => 'api_token',
            ];
        } catch (Exception $e) {
            // Fallback to user info for Global API Key
            try {
                $result = $this->request('get', '/user');
                return [
                    'id' => $result['result']['id'] ?? null,
                    'email' => $result['result']['email'] ?? null,
                    'type' => 'global_api_key',
                ];
            } catch (Exception $e2) {
                return ['error' => $e2->getMessage()];
            }
        }
    }

    /**
     * Get all zones (domains) in account
     */
    public function getZones(int $page = 1, int $perPage = 50): array
    {
        $result = $this->request('get', '/zones', [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        return [
            'zones' => array_map(function ($zone) {
                return [
                    'id' => $zone['id'],
                    'name' => $zone['name'],
                    'status' => $zone['status'],
                    'nameservers' => $zone['name_servers'] ?? [],
                ];
            }, $result['result'] ?? []),
            'total' => $result['result_info']['total_count'] ?? 0,
        ];
    }

    /**
     * Purge cache for domain
     */
    public function purgeCache(string $zoneId, bool $everything = true, array $files = []): bool
    {
        try {
            $data = $everything ? ['purge_everything' => true] : ['files' => $files];
            $this->request('post', "/zones/{$zoneId}/purge_cache", $data);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Enable development mode (disable caching)
     */
    public function setDevelopmentMode(string $zoneId, bool $enabled): bool
    {
        try {
            $this->request('patch', "/zones/{$zoneId}/settings/development_mode", [
                'value' => $enabled ? 'on' : 'off',
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get zone details including nameservers and status
     */
    public function getZoneDetails(string $zoneId): array
    {
        $result = $this->request('get', "/zones/{$zoneId}");

        return [
            'id' => $result['result']['id'],
            'name' => $result['result']['name'],
            'status' => $result['result']['status'], // active, pending, initializing, moved, deleted
            'nameservers' => $result['result']['name_servers'] ?? [],
            'original_nameservers' => $result['result']['original_name_servers'] ?? [],
            'paused' => $result['result']['paused'] ?? false,
        ];
    }

    /**
     * Trigger zone activation check (recheck nameservers)
     * This tells Cloudflare to recheck if NS records are properly configured
     */
    public function recheckZoneActivation(string $zoneId): bool
    {
        try {
            // Cloudflare API endpoint to trigger activation check
            $this->request('put', "/zones/{$zoneId}/activation_check");
            return true;
        } catch (Exception $e) {
            Log::warning('Failed to trigger zone activation check', [
                'zone_id' => $zoneId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get zone status (active, pending, etc.)
     */
    public function getZoneStatus(string $zoneId): string
    {
        try {
            $result = $this->request('get', "/zones/{$zoneId}");
            return $result['result']['status'] ?? 'unknown';
        } catch (Exception $e) {
            return 'error';
        }
    }
}
