<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ServerController extends Controller
{
    /**
     * List all servers
     */
    public function index(Request $request): JsonResponse
    {
        $query = Server::withCount('domains');

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        $servers = $query->orderByDesc('is_primary')->orderBy('name')->get();

        // Add computed fields
        $servers->each(function ($server) {
            $server->available_slots = $server->available_slots;
            $server->is_healthy = $server->isHealthy();
        });

        return $this->success($servers);
    }

    /**
     * Get single server
     */
    public function show(Server $server): JsonResponse
    {
        $server->loadCount('domains');
        $server->available_slots = $server->available_slots;
        
        return $this->success($server);
    }

    /**
     * Create server
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'ssh_host' => 'nullable|string|max:255',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_user' => 'nullable|string|max:255',
            'ssh_key' => 'nullable|string',
            'ssh_password' => 'nullable|string',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'max_domains' => 'nullable|integer|min:1',
            'nginx_config_path' => 'nullable|string|max:500',
            'sites_path' => 'nullable|string|max:500',
            'caddy_api_url' => 'nullable|url',
        ]);

        // Set defaults
        $validated['ssh_host'] = $validated['ssh_host'] ?? $validated['ip_address'];

        $server = Server::create($validated);

        return $this->success($server, 'Сервер добавлен', 201);
    }

    /**
     * Update server
     */
    public function update(Request $request, Server $server): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'ip_address' => 'sometimes|ip',
            'ssh_host' => 'nullable|string|max:255',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_user' => 'nullable|string|max:255',
            'ssh_key' => 'nullable|string',
            'ssh_password' => 'nullable|string',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'max_domains' => 'nullable|integer|min:1',
            'nginx_config_path' => 'nullable|string|max:500',
            'sites_path' => 'nullable|string|max:500',
            'caddy_api_url' => 'nullable|url',
        ]);

        $server->update($validated);

        return $this->success($server, 'Сервер обновлён');
    }

    /**
     * Delete server
     */
    public function destroy(Server $server): JsonResponse
    {
        // Check if server has domains
        if ($server->domains()->exists()) {
            return $this->error('Нельзя удалить сервер с привязанными доменами');
        }

        // Cannot delete primary server
        if ($server->is_primary) {
            return $this->error('Нельзя удалить основной сервер. Сначала назначьте другой сервер основным.');
        }

        $server->delete();

        return $this->success(null, 'Сервер удалён');
    }

    /**
     * Set server as primary
     */
    public function setPrimary(Server $server): JsonResponse
    {
        if (!$server->is_active) {
            return $this->error('Нельзя сделать неактивный сервер основным');
        }

        $server->update(['is_primary' => true]);

        return $this->success($server, 'Сервер назначен основным');
    }

    /**
     * Check server health
     */
    public function healthCheck(Server $server): JsonResponse
    {
        $checks = [
            'ping' => false,
            'http' => false,
            'caddy' => null,
            'disk' => null,
            'load' => null,
        ];

        $errors = [];

        // Check 1: Ping (simple HTTP request to IP)
        try {
            $response = Http::timeout(5)->get("http://{$server->ip_address}");
            $checks['ping'] = true;
            $checks['http'] = $response->successful() || $response->status() < 500;
        } catch (\Exception $e) {
            $errors[] = 'HTTP недоступен: ' . $e->getMessage();
        }

        // Check 2: Caddy API (if configured)
        if ($server->caddy_api_url) {
            try {
                $response = Http::timeout(5)->get($server->caddy_api_url . '/config/');
                $checks['caddy'] = $response->successful();
            } catch (\Exception $e) {
                $checks['caddy'] = false;
                $errors[] = 'Caddy API недоступен';
            }
        }

        // Determine overall status
        $status = Server::HEALTH_OK;
        if (!$checks['ping']) {
            $status = Server::HEALTH_ERROR;
        } elseif (!empty($errors)) {
            $status = Server::HEALTH_WARNING;
        }

        // Update server health
        $server->update([
            'health_status' => $status,
            'last_health_check' => now(),
        ]);

        return $this->success([
            'status' => $status,
            'checks' => $checks,
            'errors' => $errors,
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get server statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Server::count(),
            'active' => Server::where('is_active', true)->count(),
            'healthy' => Server::where('health_status', Server::HEALTH_OK)->count(),
            'total_domains' => \App\Models\Domain::count(),
            'domains_per_server' => Server::withCount('domains')
                ->get()
                ->pluck('domains_count', 'name'),
        ];

        return $this->success($stats);
    }
}
