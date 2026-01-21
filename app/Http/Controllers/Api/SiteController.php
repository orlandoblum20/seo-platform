<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Domain;
use App\Models\Template;
use App\Jobs\GenerateSiteContent;
use App\Jobs\PublishSite;
use App\Jobs\UnpublishSite;
use App\Services\Content\ContentGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SiteController extends Controller
{
    public function __construct(private ContentGenerator $contentGenerator)
    {
    }

    /**
     * List all sites with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Site::with(['domain', 'template']);

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->status($request->status);
        }

        // Filter by template
        if ($request->filled('template_id')) {
            $query->template($request->template_id);
        }

        // Filter published only
        if ($request->boolean('published_only')) {
            $query->published();
        }

        // Filter with autopost
        if ($request->boolean('with_autopost')) {
            $query->withAutopost();
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDir);

        // Pagination
        $perPage = min($request->get('per_page', 25), 100);
        $sites = $query->paginate($perPage);

        return $this->paginated($sites);
    }

    /**
     * Get single site with full details
     */
    public function show(Site $site): JsonResponse
    {
        $site->load(['domain', 'template', 'backlinks', 'autopostSettings']);
        $site->posts_count = $site->posts()->count();
        $site->published_posts_count = $site->published_posts_count;
        
        return $this->success($site);
    }

    /**
     * Create new site
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain_id' => 'required|exists:domains,id|unique:sites,domain_id',
            'template_id' => 'required|exists:templates,id',
            'title' => 'required|string|max:255',
            'niche' => 'required|string|max:255',
            'keywords' => 'required|array|min:1',
            'keywords.*' => 'string|max:100',
            'color_scheme' => 'nullable|array',
            'analytics_codes' => 'nullable|array',
            'keitaro_enabled' => 'boolean',
            'auto_generate' => 'boolean', // Auto-start content generation
        ]);

        // Check domain is available
        $domain = Domain::findOrFail($validated['domain_id']);
        if (!$domain->isAvailableForSite()) {
            return $this->error('Домен недоступен для создания сайта');
        }

        // Get template for color scheme
        $template = Template::findOrFail($validated['template_id']);
        if (empty($validated['color_scheme'])) {
            $validated['color_scheme'] = $template->getRandomColorScheme();
        }

        $site = Site::create([
            'domain_id' => $validated['domain_id'],
            'template_id' => $validated['template_id'],
            'title' => $validated['title'],
            'niche' => $validated['niche'],
            'keywords' => $validated['keywords'],
            'color_scheme' => $validated['color_scheme'],
            'analytics_codes' => $validated['analytics_codes'] ?? [],
            'keitaro_enabled' => $validated['keitaro_enabled'] ?? true,
            'status' => Site::STATUS_DRAFT,
        ]);

        // Auto-generate content if requested
        if ($request->boolean('auto_generate', true)) {
            GenerateSiteContent::dispatch($site);
            $site->update(['status' => Site::STATUS_GENERATING]);
        }

        $site->load(['domain', 'template']);

        return $this->success($site, 'Сайт создан', 201);
    }

    /**
     * Update site
     */
    public function update(Request $request, Site $site): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'niche' => 'sometimes|string|max:255',
            'keywords' => 'sometimes|array',
            'keywords.*' => 'string|max:100',
            'seo_title' => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|string',
            'content' => 'sometimes|array',
            'color_scheme' => 'nullable|array',
            'analytics_codes' => 'nullable|array',
            'custom_css' => 'nullable|string|max:50000',
            'custom_js' => 'nullable|string|max:50000',
            'custom_head' => 'nullable|string|max:10000',
            'keitaro_enabled' => 'boolean',
        ]);

        $site->update($validated);

        return $this->success($site, 'Сайт обновлён');
    }

    /**
     * Delete site
     */
    public function destroy(Site $site): JsonResponse
    {
        // Unpublish first if published
        if ($site->isPublished()) {
            UnpublishSite::dispatchSync($site);
        }

        $site->delete();

        return $this->success(null, 'Сайт удалён');
    }

    /**
     * Generate/regenerate site content
     */
    public function generate(Site $site): JsonResponse
    {
        if (!$site->canBeRegenerated()) {
            return $this->error('Сайт сейчас нельзя перегенерировать');
        }

        $site->update([
            'status' => Site::STATUS_GENERATING,
            'generation_started_at' => now(),
        ]);

        GenerateSiteContent::dispatch($site);

        return $this->success($site, 'Генерация контента запущена');
    }

    /**
     * Regenerate specific section
     */
    public function regenerateSection(Request $request, Site $site): JsonResponse
    {
        $request->validate([
            'page' => 'required|string',
            'section' => 'required|string',
        ]);

        try {
            $newContent = $this->contentGenerator->regenerateSection(
                $site,
                $request->page,
                $request->section
            );

            // Update site content
            $content = $site->content ?? [];
            $content[$request->page][$request->section] = $newContent;
            $site->update(['content' => $content]);

            return $this->success([
                'section' => $request->section,
                'content' => $newContent,
            ], 'Секция перегенерирована');

        } catch (\Exception $e) {
            return $this->error('Ошибка генерации: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Publish site
     */
    public function publish(Site $site): JsonResponse
    {
        if (!$site->canBePublished()) {
            return $this->error('Сайт нельзя опубликовать. Проверьте статус и домен.');
        }

        $site->update(['status' => Site::STATUS_PUBLISHING]);

        PublishSite::dispatch($site);

        return $this->success($site, 'Публикация запущена');
    }

    /**
     * Unpublish site
     */
    public function unpublish(Site $site): JsonResponse
    {
        if (!$site->isPublished()) {
            return $this->error('Сайт не опубликован');
        }

        UnpublishSite::dispatch($site);

        return $this->success($site, 'Сайт снимается с публикации');
    }

    /**
     * Bulk create sites
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $request->validate([
            'sites' => 'required|array|min:1|max:100',
            'sites.*.domain_id' => 'required|exists:domains,id',
            'sites.*.template_id' => 'required|exists:templates,id',
            'sites.*.title' => 'required|string|max:255',
            'sites.*.niche' => 'required|string|max:255',
            'sites.*.keywords' => 'required|array|min:1',
        ]);

        $results = ['created' => [], 'failed' => []];

        DB::beginTransaction();

        try {
            foreach ($request->sites as $siteData) {
                $domain = Domain::find($siteData['domain_id']);
                
                if (!$domain || !$domain->isAvailableForSite()) {
                    $results['failed'][] = [
                        'domain_id' => $siteData['domain_id'],
                        'error' => 'Домен недоступен',
                    ];
                    continue;
                }

                $template = Template::find($siteData['template_id']);

                $site = Site::create([
                    'domain_id' => $siteData['domain_id'],
                    'template_id' => $siteData['template_id'],
                    'title' => $siteData['title'],
                    'niche' => $siteData['niche'],
                    'keywords' => $siteData['keywords'],
                    'color_scheme' => $template->getRandomColorScheme(),
                    'status' => Site::STATUS_GENERATING,
                ]);

                GenerateSiteContent::dispatch($site);

                $results['created'][] = [
                    'id' => $site->id,
                    'domain' => $domain->domain,
                ];
            }

            DB::commit();

            return $this->success($results, 'Массовое создание запущено');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Ошибка создания: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk publish sites
     */
    public function bulkPublish(Request $request): JsonResponse
    {
        $request->validate([
            'site_ids' => 'required|array',
            'site_ids.*' => 'integer|exists:sites,id',
        ]);

        $sites = Site::whereIn('id', $request->site_ids)
            ->whereIn('status', [Site::STATUS_GENERATED, Site::STATUS_UNPUBLISHED])
            ->get();

        foreach ($sites as $site) {
            if ($site->canBePublished()) {
                $site->update(['status' => Site::STATUS_PUBLISHING]);
                PublishSite::dispatch($site);
            }
        }

        return $this->success([
            'queued' => $sites->count(),
        ], 'Публикация запущена');
    }

    /**
     * Bulk unpublish sites
     */
    public function bulkUnpublish(Request $request): JsonResponse
    {
        $request->validate([
            'site_ids' => 'required|array',
            'site_ids.*' => 'integer|exists:sites,id',
        ]);

        $sites = Site::whereIn('id', $request->site_ids)
            ->where('status', Site::STATUS_PUBLISHED)
            ->get();

        foreach ($sites as $site) {
            UnpublishSite::dispatch($site);
        }

        return $this->success([
            'queued' => $sites->count(),
        ], 'Снятие с публикации запущено');
    }

    /**
     * Get site preview (generated HTML)
     */
    public function preview(Site $site): JsonResponse
    {
        // This would generate a preview HTML
        // For now, return the content structure
        return $this->success([
            'site' => $site->only(['id', 'title', 'seo_title', 'seo_description']),
            'content' => $site->content,
            'template' => $site->template->only(['slug', 'type', 'structure']),
            'color_scheme' => $site->color_scheme,
        ]);
    }

    /**
     * Get site statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Site::count(),
            'by_status' => Site::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'published' => Site::published()->count(),
            'by_template' => Site::selectRaw('template_id, COUNT(*) as count')
                ->groupBy('template_id')
                ->with('template:id,name')
                ->get()
                ->mapWithKeys(fn($item) => [$item->template->name ?? 'Unknown' => $item->count]),
            'with_autopost' => Site::withAutopost()->count(),
            'created_today' => Site::whereDate('created_at', today())->count(),
            'published_today' => Site::whereDate('published_at', today())->count(),
        ];

        return $this->success($stats);
    }
}
