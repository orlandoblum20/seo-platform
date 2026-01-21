<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\DnsAccount;
use App\Models\Server;
use App\Services\DNS\DnsManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DomainController extends Controller
{
    private DnsManager $dnsManager;

    public function __construct(DnsManager $dnsManager)
    {
        $this->dnsManager = $dnsManager;
    }

    /**
     * List all domains with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Domain::with(['dnsAccount', 'server', 'site']);

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->status($request->status);
        }

        // Filter by DNS account
        if ($request->has('dns_account_id')) {
            $query->dnsAccount($request->dns_account_id);
        }

        // Filter by server
        if ($request->has('server_id')) {
            $query->server($request->server_id);
        }

        // Filter by NS servers
        if ($request->has('nameservers')) {
            $ns = $request->nameservers;
            if (is_string($ns)) {
                $ns = explode(',', $ns);
            }
            $query->where(function ($q) use ($ns) {
                foreach ($ns as $server) {
                    $q->orWhereJsonContains('nameservers', trim($server));
                }
            });
        }

        // Filter only available (without site)
        if ($request->boolean('available_only')) {
            $query->available();
        }

        // Filter with errors
        if ($request->boolean('with_errors')) {
            $query->withErrors();
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDir);

        // Pagination
        $perPage = min($request->get('per_page', 25), 100);
        $domains = $query->paginate($perPage);

        return $this->paginated($domains);
    }

    /**
     * Get single domain
     */
    public function show(Domain $domain): JsonResponse
    {
        $domain->load(['dnsAccount', 'server', 'site']);
        
        return $this->success($domain);
    }

    /**
     * Import domains in bulk
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'domains' => 'required|string',
            'dns_account_id' => 'required|exists:dns_accounts,id',
            'server_id' => 'nullable|exists:servers,id',
        ]);

        $account = DnsAccount::findOrFail($request->dns_account_id);
        $server = $request->server_id ? Server::find($request->server_id) : Server::primary();

        // Parse domains (one per line)
        $domainsList = array_filter(
            array_map('trim', explode("\n", $request->domains))
        );

        if (empty($domainsList)) {
            return $this->error('Не указаны домены для импорта');
        }

        // Validate domain format
        $invalidDomains = [];
        $validDomains = [];
        
        foreach ($domainsList as $domain) {
            if (preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/i', $domain)) {
                $validDomains[] = strtolower($domain);
            } else {
                $invalidDomains[] = $domain;
            }
        }

        if (empty($validDomains)) {
            return $this->error('Все домены имеют неверный формат', 400, [
                'invalid_domains' => $invalidDomains,
            ]);
        }

        // Add domains via DNS Manager
        $results = $this->dnsManager->addDomainsBulk($validDomains, $account, $server);

        return $this->success([
            'success' => $results['success'],
            'failed' => $results['failed'],
            'invalid_format' => $invalidDomains,
            'summary' => [
                'total_submitted' => count($domainsList),
                'added' => count($results['success']),
                'failed' => count($results['failed']),
                'invalid' => count($invalidDomains),
            ],
        ], 'Импорт завершён');
    }

    /**
     * Update domain
     */
    public function update(Request $request, Domain $domain): JsonResponse
    {
        $request->validate([
            'dr_rating' => 'nullable|integer|min:0|max:100',
            'iks_rating' => 'nullable|integer|min:0',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'registrar' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $domain->update($request->only([
            'dr_rating',
            'iks_rating',
            'purchase_date',
            'purchase_price',
            'registrar',
            'expiry_date',
            'notes',
        ]));

        return $this->success($domain, 'Домен обновлён');
    }

    /**
     * Delete domain
     */
    public function destroy(Domain $domain): JsonResponse
    {
        // Check if domain has a published site
        if ($domain->site && $domain->site->isPublished()) {
            return $this->error('Нельзя удалить домен с опубликованным сайтом');
        }

        $this->dnsManager->removeDomain($domain);

        return $this->success(null, 'Домен удалён');
    }

    /**
     * Bulk delete domains
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'domain_ids' => 'required|array',
            'domain_ids.*' => 'integer|exists:domains,id',
        ]);

        $domains = Domain::whereIn('id', $request->domain_ids)
            ->whereDoesntHave('site', function ($q) {
                $q->where('status', 'published');
            })
            ->get();

        $deleted = 0;
        foreach ($domains as $domain) {
            $this->dnsManager->removeDomain($domain);
            $deleted++;
        }

        return $this->success([
            'deleted' => $deleted,
            'skipped' => count($request->domain_ids) - $deleted,
        ], 'Удаление завершено');
    }

    /**
     * Update IP for multiple domains
     */
    public function updateIp(Request $request): JsonResponse
    {
        $request->validate([
            'domain_ids' => 'required|array',
            'domain_ids.*' => 'integer|exists:domains,id',
            'ip_address' => 'required|ip',
        ]);

        $results = $this->dnsManager->updateIpBulk(
            $request->domain_ids,
            $request->ip_address
        );

        return $this->success([
            'success' => $results['success'],
            'failed' => $results['failed'],
        ], 'IP обновлён');
    }

    /**
     * Check SSL status for domain
     */
    public function checkSsl(Domain $domain): JsonResponse
    {
        $status = $this->dnsManager->checkSslStatus($domain);

        return $this->success([
            'domain' => $domain->domain,
            'ssl_status' => $status,
            'ssl_status_text' => Domain::getSslStatuses()[$status] ?? $status,
        ]);
    }

    /**
     * Move domains to different DNS account
     */
    public function moveToDnsAccount(Request $request): JsonResponse
    {
        $request->validate([
            'domain_ids' => 'required|array',
            'domain_ids.*' => 'integer|exists:domains,id',
            'dns_account_id' => 'required|exists:dns_accounts,id',
        ]);

        // This is a complex operation - for now just update the reference
        // Full migration would require removing from old and adding to new DNS
        Domain::whereIn('id', $request->domain_ids)
            ->update(['dns_account_id' => $request->dns_account_id]);

        return $this->success(null, 'Домены перемещены');
    }

    /**
     * Get domain statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Domain::count(),
            'by_status' => Domain::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_ssl_status' => Domain::selectRaw('ssl_status, COUNT(*) as count')
                ->groupBy('ssl_status')
                ->pluck('count', 'ssl_status'),
            'available' => Domain::available()->count(),
            'with_sites' => Domain::has('site')->count(),
            'with_errors' => Domain::withErrors()->count(),
        ];

        return $this->success($stats);
    }

    /**
     * Get filter options (unique NS servers, DNS accounts)
     */
    public function filterOptions(): JsonResponse
    {
        // Get unique NS servers
        $nsServers = Domain::whereNotNull('nameservers')
            ->select('nameservers')
            ->distinct()
            ->get()
            ->pluck('nameservers')
            ->filter()
            ->map(function ($ns) {
                // Handle both array and string formats
                if (is_string($ns)) {
                    $ns = json_decode($ns, true) ?: [$ns];
                }
                return is_array($ns) ? $ns : [$ns];
            })
            ->unique(function ($ns) {
                return implode('|', $ns);
            })
            ->values();

        // Get DNS accounts with domain count
        $dnsAccounts = DnsAccount::withCount('domains')
            ->get(['id', 'name', 'provider']);

        return $this->success([
            'ns_servers' => $nsServers,
            'dns_accounts' => $dnsAccounts,
        ]);
    }

    /**
     * Export domains list
     */
    public function export(Request $request): JsonResponse
    {
        $query = Domain::query();

        // Apply filters
        if ($request->has('status')) {
            $query->status($request->status);
        }
        if ($request->has('dns_account_id')) {
            $query->dnsAccount($request->dns_account_id);
        }
        if ($request->has('nameservers')) {
            $query->whereJsonContains('nameservers', $request->nameservers);
        }
        if ($request->has('domain_ids')) {
            $query->whereIn('id', $request->domain_ids);
        }

        $domains = $query->pluck('domain');

        return $this->success([
            'domains' => $domains,
            'count' => $domains->count(),
        ]);
    }

    /**
     * Recheck domain status at DNS provider
     */
    public function recheckStatus(Domain $domain): JsonResponse
    {
        $result = $this->dnsManager->recheckDomainStatus($domain);

        if ($result['success']) {
            $domain->refresh();
            return $this->success([
                'domain' => $domain,
                'provider_status' => $result['status'] ?? null,
                'nameservers' => $result['nameservers'] ?? $domain->nameservers,
            ], 'Статус обновлён');
        }

        return $this->error($result['error'] ?? 'Ошибка проверки статуса', 500);
    }

    /**
     * Bulk recheck domain status
     */
    public function bulkRecheckStatus(Request $request): JsonResponse
    {
        $request->validate([
            'domain_ids' => 'required|array',
            'domain_ids.*' => 'integer|exists:domains,id',
        ]);

        $results = $this->dnsManager->recheckDomainStatusBulk($request->domain_ids);

        return $this->success([
            'success' => $results['success'],
            'failed' => $results['failed'],
            'summary' => [
                'checked' => count($results['success']),
                'failed' => count($results['failed']),
            ],
        ], 'Проверка статуса завершена');
    }

    /**
     * Get SSL details for a domain
     */
    public function getSslDetails(Domain $domain): JsonResponse
    {
        if (!$domain->dnsAccount) {
            return $this->error('Домен не привязан к DNS аккаунту', 400);
        }

        try {
            // For Cloudflare
            if ($domain->dnsAccount->isCloudflare()) {
                $zoneId = $domain->cloudflare_zone_id;
                if (!$zoneId) {
                    return $this->error('Домен не имеет zone_id в Cloudflare', 400);
                }

                $service = $this->dnsManager->getService($domain->dnsAccount);
                $details = $service->getSslDetails($zoneId);

                return $this->success([
                    'domain' => $domain->domain,
                    'provider' => 'cloudflare',
                    'ssl' => $details,
                ]);
            }

            // For DNSPOD - SSL via Caddy
            if ($domain->dnsAccount->isDnspod()) {
                $caddyManager = app(\App\Services\SSL\CaddyManager::class);
                $details = $caddyManager->getSslDetails($domain->domain);

                return $this->success([
                    'domain' => $domain->domain,
                    'provider' => 'caddy',
                    'ssl' => $details,
                ]);
            }

            return $this->error('Неизвестный DNS провайдер', 400);

        } catch (\Exception $e) {
            return $this->error('Ошибка получения SSL информации: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Setup SSL for a domain (force)
     */
    public function setupSsl(Domain $domain): JsonResponse
    {
        if (!$domain->dnsAccount) {
            return $this->error('Домен не привязан к DNS аккаунту', 400);
        }

        try {
            // For Cloudflare
            if ($domain->dnsAccount->isCloudflare()) {
                return $this->setupCloudfareSsl($domain);
            }

            // For DNSPOD - SSL via Caddy
            if ($domain->dnsAccount->isDnspod()) {
                return $this->setupCaddySsl($domain);
            }

            return $this->error('Неизвестный DNS провайдер', 400);

        } catch (\Exception $e) {
            return $this->error('Ошибка настройки SSL: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Setup SSL via Cloudflare
     */
    private function setupCloudfareSsl(Domain $domain): JsonResponse
    {
        $zoneId = $domain->cloudflare_zone_id;
        if (!$zoneId) {
            return $this->error('Домен не имеет zone_id в Cloudflare', 400);
        }

        $service = $this->dnsManager->getService($domain->dnsAccount);
        $zoneDetails = $service->getZoneDetails($zoneId);

        if ($zoneDetails['status'] !== 'active') {
            return $this->error('Домен ещё не активирован в Cloudflare. Сначала настройте NS записи.', 400);
        }

        // Setup SSL
        $success = $service->setupSsl($zoneId);

        if (!$success) {
            return $this->error('Не удалось настроить SSL', 500);
        }

        // Check SSL status
        $sslStatus = $service->getSslStatus($zoneId);

        // Update domain
        $newStatus = $sslStatus === 'active' ? Domain::STATUS_ACTIVE : Domain::STATUS_SSL_PENDING;
        $newSslStatus = $sslStatus === 'active' ? Domain::SSL_ACTIVE : Domain::SSL_PENDING;

        $domain->update([
            'status' => $newStatus,
            'ssl_status' => $newSslStatus,
            'last_check_at' => now(),
        ]);

        return $this->success([
            'domain' => $domain->domain,
            'provider' => 'cloudflare',
            'status' => $newStatus,
            'ssl_status' => $newSslStatus,
            'ssl_mode' => 'full',
            'message' => $sslStatus === 'active' 
                ? 'SSL активен, домен полностью готов!' 
                : 'SSL настроен (режим Full), ожидание выпуска сертификата...',
        ]);
    }

    /**
     * Setup SSL via Caddy + Let's Encrypt (for DNSPOD domains)
     */
    private function setupCaddySsl(Domain $domain): JsonResponse
    {
        $domainId = $domain->dnspod_domain_id;
        if (!$domainId) {
            return $this->error('Домен не имеет ID в DNSPOD', 400);
        }

        // Check domain status in DNSPOD API
        try {
            $service = $this->dnsManager->getService($domain->dnsAccount);
            $domainInfo = $service->getZoneDetails($domainId);
            
            if ($domainInfo['status'] !== 'active') {
                return $this->error('Домен ещё не активирован в DNSPOD. Пропишите NS: a.dnspod.com, c.dnspod.com у регистратора и дождитесь активации.', 400);
            }
        } catch (\Exception $e) {
            return $this->error('Ошибка проверки статуса в DNSPOD: ' . $e->getMessage(), 500);
        }

        // Setup SSL via Caddy
        // Caddy runs on the same server, so always use localhost:8080 (Docker port)
        $caddyManager = app(\App\Services\SSL\CaddyManager::class);
        
        $success = $caddyManager->addDomain($domain->domain);

        if (!$success) {
            return $this->error('Не удалось добавить домен в Caddy', 500);
        }

        // Check if SSL is already active
        $sslActive = $caddyManager->checkSslStatus($domain->domain);

        // Update domain status
        $newStatus = $sslActive ? Domain::STATUS_ACTIVE : Domain::STATUS_SSL_PENDING;
        $newSslStatus = $sslActive ? Domain::SSL_ACTIVE : Domain::SSL_PENDING;

        $domain->update([
            'status' => $newStatus,
            'ssl_status' => $newSslStatus,
            'last_check_at' => now(),
        ]);

        return $this->success([
            'domain' => $domain->domain,
            'provider' => 'caddy',
            'status' => $newStatus,
            'ssl_status' => $newSslStatus,
            'message' => $sslActive 
                ? 'SSL сертификат Let\'s Encrypt активен!' 
                : 'Домен добавлен в Caddy, ожидание выпуска сертификата Let\'s Encrypt...',
        ]);
    }

    /**
     * Bulk setup SSL for multiple domains
     */
    public function bulkSetupSsl(Request $request): JsonResponse
    {
        $request->validate([
            'domain_ids' => 'required|array',
            'domain_ids.*' => 'integer|exists:domains,id',
        ]);

        $results = ['success' => [], 'failed' => []];

        // Get domains with zone IDs (both Cloudflare and DNSPOD)
        $domains = Domain::whereIn('id', $request->domain_ids)
            ->where(function ($q) {
                $q->whereNotNull('cloudflare_zone_id')
                  ->orWhereNotNull('dnspod_domain_id');
            })
            ->with('dnsAccount', 'server')
            ->get();

        $caddyManager = app(\App\Services\SSL\CaddyManager::class);

        foreach ($domains as $domain) {
            try {
                // Cloudflare domains
                if ($domain->dnsAccount && $domain->dnsAccount->isCloudflare()) {
                    $result = $this->bulkSetupCloudfareSsl($domain);
                }
                // DNSPOD domains
                elseif ($domain->dnsAccount && $domain->dnsAccount->isDnspod()) {
                    $result = $this->bulkSetupCaddySsl($domain, $caddyManager);
                }
                else {
                    $result = [
                        'success' => false,
                        'error' => 'Неизвестный DNS провайдер',
                    ];
                }

                if ($result['success']) {
                    $results['success'][] = [
                        'domain' => $domain->domain,
                        'status' => $result['status'] ?? 'ssl_pending',
                        'provider' => $result['provider'] ?? 'unknown',
                    ];
                } else {
                    $results['failed'][] = [
                        'domain' => $domain->domain,
                        'error' => $result['error'] ?? 'Неизвестная ошибка',
                    ];
                }

                // Rate limiting
                usleep(300000);

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'domain' => $domain->domain,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->success([
            'success' => $results['success'],
            'failed' => $results['failed'],
            'summary' => [
                'configured' => count($results['success']),
                'failed' => count($results['failed']),
            ],
        ], 'Настройка SSL завершена');
    }

    /**
     * Bulk setup SSL for Cloudflare domain
     */
    private function bulkSetupCloudfareSsl(Domain $domain): array
    {
        $service = $this->dnsManager->getService($domain->dnsAccount);
        $zoneId = $domain->cloudflare_zone_id;

        // Check zone status
        $zoneDetails = $service->getZoneDetails($zoneId);
        
        if ($zoneDetails['status'] !== 'active') {
            return [
                'success' => false,
                'error' => 'Зона не активна (NS не настроены)',
            ];
        }

        // Setup SSL
        $success = $service->setupSsl($zoneId);
        
        if ($success) {
            $sslStatus = $service->getSslStatus($zoneId);
            $newStatus = $sslStatus === 'active' ? Domain::STATUS_ACTIVE : Domain::STATUS_SSL_PENDING;
            
            $domain->update([
                'status' => $newStatus,
                'ssl_status' => $sslStatus === 'active' ? Domain::SSL_ACTIVE : Domain::SSL_PENDING,
                'last_check_at' => now(),
            ]);

            return [
                'success' => true,
                'status' => $newStatus,
                'provider' => 'cloudflare',
            ];
        }

        return [
            'success' => false,
            'error' => 'Не удалось настроить SSL',
        ];
    }

    /**
     * Bulk setup SSL via Caddy for DNSPOD domain
     */
    private function bulkSetupCaddySsl(Domain $domain, $caddyManager): array
    {
        // Check domain status in DNSPOD API
        try {
            $service = $this->dnsManager->getService($domain->dnsAccount);
            $domainInfo = $service->getZoneDetails($domain->dnspod_domain_id);
            
            if ($domainInfo['status'] !== 'active') {
                return [
                    'success' => false,
                    'error' => 'Домен не активирован в DNSPOD',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Ошибка проверки статуса DNSPOD: ' . $e->getMessage(),
            ];
        }

        // Setup SSL via Caddy (always localhost:8080)
        $success = $caddyManager->addDomain($domain->domain);

        if ($success) {
            $sslActive = $caddyManager->checkSslStatus($domain->domain);
            $newStatus = $sslActive ? Domain::STATUS_ACTIVE : Domain::STATUS_SSL_PENDING;

            $domain->update([
                'status' => $newStatus,
                'ssl_status' => $sslActive ? Domain::SSL_ACTIVE : Domain::SSL_PENDING,
                'last_check_at' => now(),
            ]);

            return [
                'success' => true,
                'status' => $newStatus,
                'provider' => 'caddy',
            ];
        }

        return [
            'success' => false,
            'error' => 'Не удалось добавить домен в Caddy',
        ];
    }
}
