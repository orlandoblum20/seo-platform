<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Site;
use App\Models\Post;
use App\Models\DnsAccount;
use App\Models\Server;
use App\Models\AutopostSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Get dashboard overview
     */
    public function index(): JsonResponse
    {
        $data = [
            // Quick stats
            'stats' => [
                'domains' => [
                    'total' => Domain::count(),
                    'active' => Domain::where('status', Domain::STATUS_ACTIVE)->count(),
                    'available' => Domain::available()->count(),
                    'with_errors' => Domain::withErrors()->count(),
                ],
                'sites' => [
                    'total' => Site::count(),
                    'published' => Site::published()->count(),
                    'draft' => Site::where('status', Site::STATUS_DRAFT)->count(),
                    'generating' => Site::where('status', Site::STATUS_GENERATING)->count(),
                ],
                'posts' => [
                    'total' => Post::count(),
                    'published' => Post::published()->count(),
                    'scheduled' => Post::scheduled()->count(),
                    'today' => Post::whereDate('published_at', today())->count(),
                ],
                'autopost' => [
                    'enabled_sites' => AutopostSetting::where('is_enabled', true)->count(),
                    'next_24h' => AutopostSetting::where('is_enabled', true)
                        ->where('next_post_at', '<=', now()->addDay())
                        ->count(),
                ],
            ],

            // Recent activity
            'recent_sites' => Site::with('domain')
                ->latest()
                ->take(5)
                ->get(['id', 'domain_id', 'title', 'status', 'created_at']),

            'recent_posts' => Post::with('site.domain')
                ->latest()
                ->take(5)
                ->get(['id', 'site_id', 'title', 'type', 'status', 'created_at']),

            // Errors/warnings
            'alerts' => $this->getAlerts(),

            // Infrastructure status
            'infrastructure' => [
                'dns_accounts' => DnsAccount::where('is_active', true)->count(),
                'servers' => Server::where('is_active', true)->count(),
                'primary_server' => Server::primary()?->only(['id', 'name', 'ip_address', 'health_status']),
            ],
        ];

        return $this->success($data);
    }

    /**
     * Get detailed statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $period = $request->get('period', '7d');
        $startDate = match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            '90d' => now()->subMonths(3),
            default => now()->subWeek(),
        };

        $stats = [
            // Sites created over time
            'sites_created' => Site::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date'),

            // Sites published over time
            'sites_published' => Site::where('published_at', '>=', $startDate)
                ->whereNotNull('published_at')
                ->selectRaw('DATE(published_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date'),

            // Posts created over time
            'posts_created' => Post::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date'),

            // Domains added over time
            'domains_added' => Domain::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date'),

            // Sites by template
            'sites_by_template' => Site::selectRaw('template_id, COUNT(*) as count')
                ->groupBy('template_id')
                ->with('template:id,name')
                ->get()
                ->mapWithKeys(fn($item) => [$item->template->name ?? 'Unknown' => $item->count]),

            // Sites by status
            'sites_by_status' => Site::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),

            // Posts by type
            'posts_by_type' => Post::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),

            // Domains by DNS provider
            'domains_by_provider' => Domain::join('dns_accounts', 'domains.dns_account_id', '=', 'dns_accounts.id')
                ->selectRaw('dns_accounts.provider, COUNT(*) as count')
                ->groupBy('dns_accounts.provider')
                ->pluck('count', 'provider'),
        ];

        return $this->success($stats);
    }

    /**
     * Get activity log
     */
    public function activityLog(Request $request): JsonResponse
    {
        $query = Activity::with('causer')
            ->latest();

        if ($request->has('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->has('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        $perPage = min($request->get('per_page', 50), 100);
        $activities = $query->paginate($perPage);

        return $this->paginated($activities);
    }

    /**
     * Get queue status
     */
    public function queueStatus(): JsonResponse
    {
        $status = [
            'jobs' => [
                'pending' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
            ],
            'batches' => [
                'pending' => DB::table('job_batches')
                    ->whereNull('finished_at')
                    ->count(),
                'recent' => DB::table('job_batches')
                    ->latest('created_at')
                    ->take(5)
                    ->get(['id', 'name', 'total_jobs', 'pending_jobs', 'failed_jobs', 'created_at', 'finished_at']),
            ],
            'generating_sites' => Site::where('status', Site::STATUS_GENERATING)->count(),
            'publishing_sites' => Site::where('status', Site::STATUS_PUBLISHING)->count(),
            'generating_posts' => Post::where('status', Post::STATUS_GENERATING)->count(),
        ];

        return $this->success($status);
    }

    /**
     * Get system alerts
     */
    private function getAlerts(): array
    {
        $alerts = [];

        // Check for domain errors
        $domainErrors = Domain::withErrors()->count();
        if ($domainErrors > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Есть {$domainErrors} доменов с ошибками",
                'action' => '/domains?status=error',
            ];
        }

        // Check for SSL pending
        $sslPending = Domain::where('ssl_status', Domain::SSL_PENDING)
            ->where('created_at', '<', now()->subHours(2))
            ->count();
        if ($sslPending > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$sslPending} доменов ожидают SSL более 2 часов",
                'action' => '/domains?ssl_status=pending',
            ];
        }

        // Check for failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs > 0) {
            $alerts[] = [
                'type' => 'error',
                'message' => "Есть {$failedJobs} неудачных задач в очереди",
                'action' => '/settings/queue',
            ];
        }

        // Check for unhealthy servers
        $unhealthyServers = Server::where('health_status', Server::HEALTH_ERROR)->count();
        if ($unhealthyServers > 0) {
            $alerts[] = [
                'type' => 'error',
                'message' => "{$unhealthyServers} серверов недоступны",
                'action' => '/servers',
            ];
        }

        // Check AI quota
        $lowQuotaProviders = \App\Models\AiSetting::where('is_active', true)
            ->whereNotNull('daily_limit')
            ->whereRaw('requests_today >= daily_limit * 0.9')
            ->count();
        if ($lowQuotaProviders > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "AI провайдеры близки к лимиту запросов",
                'action' => '/settings/ai',
            ];
        }

        return $alerts;
    }
}
