<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backlink;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BacklinkController extends Controller
{
    /**
     * List all backlinks
     */
    public function index(Request $request): JsonResponse
    {
        $query = Backlink::withCount('sites');

        if ($request->has('group')) {
            $query->group($request->group);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ILIKE', "%{$request->search}%")
                  ->orWhere('url', 'ILIKE', "%{$request->search}%");
            });
        }

        $backlinks = $query->ordered()->get();

        return $this->success($backlinks);
    }

    /**
     * Get single backlink
     */
    public function show(Backlink $backlink): JsonResponse
    {
        $backlink->loadCount('sites');
        $backlink->load('sites:id,title,domain_id');
        
        return $this->success($backlink);
    }

    /**
     * Create backlink
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'anchors' => 'required|array|min:1',
            'anchors.*' => 'string|max:255',
            'group' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:1|max:100',
        ]);

        $backlink = Backlink::create($validated);

        return $this->success($backlink, 'Бэклинк создан', 201);
    }

    /**
     * Update backlink
     */
    public function update(Request $request, Backlink $backlink): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url|max:500',
            'anchors' => 'sometimes|array',
            'anchors.*' => 'string|max:255',
            'group' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:1|max:100',
        ]);

        $backlink->update($validated);

        return $this->success($backlink, 'Бэклинк обновлён');
    }

    /**
     * Delete backlink
     */
    public function destroy(Backlink $backlink): JsonResponse
    {
        // Detach from all sites first
        $backlink->sites()->detach();
        $backlink->delete();

        return $this->success(null, 'Бэклинк удалён');
    }

    /**
     * Add anchor to backlink
     */
    public function addAnchor(Request $request, Backlink $backlink): JsonResponse
    {
        $request->validate([
            'anchor' => 'required|string|max:255',
        ]);

        $backlink->addAnchor($request->anchor);

        return $this->success($backlink, 'Анкор добавлен');
    }

    /**
     * Assign backlink to sites
     */
    public function assignToSites(Request $request): JsonResponse
    {
        $request->validate([
            'backlink_id' => 'required|exists:backlinks,id',
            'site_ids' => 'required|array|min:1',
            'site_ids.*' => 'integer|exists:sites,id',
            'placement' => 'required|in:header,footer,content,sidebar',
            'is_nofollow' => 'boolean',
            'anchor_mode' => 'required|in:random,specific,from_keywords',
            'specific_anchor' => 'required_if:anchor_mode,specific|string|max:255',
        ]);

        $backlink = Backlink::findOrFail($request->backlink_id);
        $sites = Site::whereIn('id', $request->site_ids)->get();

        $assigned = 0;
        $skipped = 0;

        foreach ($sites as $site) {
            // Check if already assigned
            if ($site->backlinks()->where('backlink_id', $backlink->id)->where('placement', $request->placement)->exists()) {
                $skipped++;
                continue;
            }

            // Determine anchor
            $anchor = match ($request->anchor_mode) {
                'specific' => $request->specific_anchor,
                'from_keywords' => $backlink->getAnchorByKeywords($site->keywords ?? []),
                default => $backlink->getRandomAnchor(),
            };

            $site->backlinks()->attach($backlink->id, [
                'anchor' => $anchor,
                'placement' => $request->placement,
                'is_nofollow' => $request->boolean('is_nofollow', false),
            ]);

            $assigned++;
        }

        return $this->success([
            'assigned' => $assigned,
            'skipped' => $skipped,
        ], 'Бэклинки назначены');
    }

    /**
     * Remove backlink from sites
     */
    public function removeFromSites(Request $request): JsonResponse
    {
        $request->validate([
            'backlink_id' => 'required|exists:backlinks,id',
            'site_ids' => 'required|array|min:1',
            'site_ids.*' => 'integer|exists:sites,id',
            'placement' => 'nullable|in:header,footer,content,sidebar',
        ]);

        $query = DB::table('site_backlinks')
            ->where('backlink_id', $request->backlink_id)
            ->whereIn('site_id', $request->site_ids);

        if ($request->has('placement')) {
            $query->where('placement', $request->placement);
        }

        $deleted = $query->delete();

        return $this->success([
            'removed' => $deleted,
        ], 'Бэклинки удалены с сайтов');
    }

    /**
     * Randomize anchors for backlink across sites
     */
    public function randomizeAnchors(Request $request): JsonResponse
    {
        $request->validate([
            'backlink_id' => 'required|exists:backlinks,id',
            'anchors' => 'required|array|min:1',
            'anchors.*' => 'string|max:255',
            'site_ids' => 'nullable|array',
            'site_ids.*' => 'integer|exists:sites,id',
        ]);

        $backlink = Backlink::findOrFail($request->backlink_id);
        $anchors = $request->anchors;

        // Update backlink anchors
        $backlink->update(['anchors' => $anchors]);

        // Get sites to update
        $query = DB::table('site_backlinks')->where('backlink_id', $backlink->id);
        
        if ($request->has('site_ids')) {
            $query->whereIn('site_id', $request->site_ids);
        }

        $siteBacklinks = $query->get();

        // Randomize anchors
        foreach ($siteBacklinks as $sb) {
            $randomAnchor = $anchors[array_rand($anchors)];
            DB::table('site_backlinks')
                ->where('id', $sb->id)
                ->update(['anchor' => $randomAnchor]);
        }

        return $this->success([
            'updated' => count($siteBacklinks),
        ], 'Анкоры рандомизированы');
    }

    /**
     * Get all groups
     */
    public function groups(): JsonResponse
    {
        return $this->success(Backlink::getGroups());
    }

    /**
     * Get backlink statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Backlink::count(),
            'active' => Backlink::active()->count(),
            'total_placements' => DB::table('site_backlinks')->count(),
            'by_group' => Backlink::selectRaw('COALESCE("group", \'Без группы\') as grp, COUNT(*) as count')
                ->groupBy('group')
                ->pluck('count', 'grp'),
            'by_placement' => DB::table('site_backlinks')
                ->selectRaw('placement, COUNT(*) as count')
                ->groupBy('placement')
                ->pluck('count', 'placement'),
        ];

        return $this->success($stats);
    }
}
