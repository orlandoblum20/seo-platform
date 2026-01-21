<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DnsAccount;
use App\Services\DNS\DnsManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class DnsAccountController extends Controller
{
    public function __construct(private DnsManager $dnsManager)
    {
    }

    /**
     * List all DNS accounts
     */
    public function index(Request $request): JsonResponse
    {
        $query = DnsAccount::withCount('domains');

        if ($request->has('provider')) {
            $query->provider($request->provider);
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $accounts = $query->orderBy('name')->get();

        return $this->success($accounts);
    }

    /**
     * Get single DNS account
     */
    public function show(DnsAccount $dnsAccount): JsonResponse
    {
        $dnsAccount->loadCount('domains');
        
        return $this->success($dnsAccount);
    }

    /**
     * Create DNS account
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => ['required', Rule::in(array_keys(DnsAccount::getProviders()))],
            'api_key' => 'required|string',
            'api_secret' => 'nullable|string', // Required for DNSPOD
            'email' => 'nullable|email',
            'account_id' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Validate DNSPOD requires api_secret
        if ($validated['provider'] === DnsAccount::PROVIDER_DNSPOD && empty($validated['api_secret'])) {
            return $this->error('DNSPOD требует API ID и API Token', 422);
        }

        $account = DnsAccount::create($validated);

        // Verify connection
        if (!$this->dnsManager->verifyAccount($account)) {
            $account->delete();
            return $this->error('Не удалось подключиться к API. Проверьте ключи.', 422);
        }

        // Get account info and account_id for Cloudflare
        try {
            $service = $this->dnsManager->getService($account);
            $info = $service->getAccountInfo();
            
            // For Cloudflare with Global API Key, fetch account_id from accounts endpoint
            if ($account->isCloudflare() && empty($account->account_id) && !empty($account->email)) {
                $accountId = $this->fetchCloudflareAccountId($account);
                if ($accountId) {
                    $account->update(['account_id' => $accountId]);
                }
            } elseif (isset($info['id']) && empty($account->account_id)) {
                $account->update(['account_id' => $info['id']]);
            }
        } catch (\Exception $e) {
            // Non-critical, continue
        }

        $account->loadCount('domains');

        return $this->success($account, 'DNS аккаунт добавлен', 201);
    }

    /**
     * Update DNS account
     */
    public function update(Request $request, DnsAccount $dnsAccount): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'api_key' => 'sometimes|string',
            'api_secret' => 'nullable|string',
            'email' => 'nullable|email',
            'account_id' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $dnsAccount->update($validated);

        // Re-verify if API key changed
        if (isset($validated['api_key']) || isset($validated['api_secret'])) {
            if (!$this->dnsManager->verifyAccount($dnsAccount)) {
                return $this->error('Новые ключи не работают. Аккаунт обновлён, но соединение не проверено.', 200);
            }
        }

        return $this->success($dnsAccount, 'DNS аккаунт обновлён');
    }

    /**
     * Delete DNS account
     */
    public function destroy(DnsAccount $dnsAccount): JsonResponse
    {
        // Check if account has domains
        if ($dnsAccount->domains()->exists()) {
            return $this->error('Нельзя удалить аккаунт с привязанными доменами. Сначала переместите или удалите домены.');
        }

        $dnsAccount->delete();

        return $this->success(null, 'DNS аккаунт удалён');
    }

    /**
     * Verify account connection
     */
    public function verify(DnsAccount $dnsAccount): JsonResponse
    {
        $isValid = $this->dnsManager->verifyAccount($dnsAccount);

        if ($isValid) {
            $service = $this->dnsManager->getService($dnsAccount);
            $info = $service->getAccountInfo();

            return $this->success([
                'valid' => true,
                'account_info' => $info,
            ], 'Соединение успешно');
        }

        return $this->error('Не удалось подключиться к API', 422);
    }

    /**
     * Sync domains from DNS provider
     */
    public function sync(DnsAccount $dnsAccount): JsonResponse
    {
        try {
            $service = $this->dnsManager->getService($dnsAccount);
            
            // Get zones from provider
            if ($dnsAccount->isCloudflare()) {
                $result = $service->getZones();
            } else {
                $result = $service->getDomains();
            }

            $synced = 0;
            $skipped = 0;

            foreach ($result['zones'] as $zone) {
                // Check if domain already exists
                $exists = \App\Models\Domain::where('domain', $zone['name'])->exists();
                
                if (!$exists) {
                    \App\Models\Domain::create([
                        'domain' => $zone['name'],
                        'dns_account_id' => $dnsAccount->id,
                        'status' => $zone['status'] === 'active' ? 'active' : 'pending',
                        'ssl_status' => 'none',
                        'cloudflare_zone_id' => $dnsAccount->isCloudflare() ? $zone['id'] : null,
                        'dnspod_domain_id' => $dnsAccount->isDnspod() ? $zone['id'] : null,
                    ]);
                    $synced++;
                } else {
                    $skipped++;
                }
            }

            $dnsAccount->update(['last_sync_at' => now()]);

            return $this->success([
                'synced' => $synced,
                'skipped' => $skipped,
                'total_in_provider' => $result['total'] ?? count($result['zones']),
            ], 'Синхронизация завершена');

        } catch (\Exception $e) {
            return $this->error('Ошибка синхронизации: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Fetch Cloudflare account ID using Global API Key
     */
    private function fetchCloudflareAccountId(DnsAccount $account): ?string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Auth-Email' => $account->email,
                'X-Auth-Key' => $account->api_key,
                'Content-Type' => 'application/json',
            ])->get('https://api.cloudflare.com/client/v4/accounts');

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['result'][0]['id'])) {
                    return $data['result'][0]['id'];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to fetch Cloudflare account ID', [
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
}
