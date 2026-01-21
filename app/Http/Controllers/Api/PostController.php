<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Site;
use App\Models\AutopostSetting;
use App\Jobs\GeneratePost;
use App\Jobs\PublishPost;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    /**
     * List posts
     */
    public function index(Request $request): JsonResponse
    {
        $query = Post::with('site.domain');

        if ($request->has('site_id')) {
            $query->site($request->site_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->type($request->type);
        }

        if ($request->has('search')) {
            $query->where('title', 'ILIKE', "%{$request->search}%");
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDir);

        $perPage = min($request->get('per_page', 25), 100);
        $posts = $query->paginate($perPage);

        return $this->paginated($posts);
    }

    /**
     * Get single post
     */
    public function show(Post $post): JsonResponse
    {
        $post->load('site.domain');
        
        return $this->success($post);
    }

    /**
     * Create post
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'type' => ['required', Rule::in(array_keys(Post::getTypes()))],
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'seo_title' => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:160',
            'status' => ['sometimes', Rule::in(['draft', 'scheduled'])],
            'scheduled_at' => 'required_if:status,scheduled|nullable|date|after:now',
        ]);

        $post = Post::create($validated);

        return $this->success($post, 'Пост создан', 201);
    }

    /**
     * Update post
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'sometimes|string',
            'seo_title' => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:160',
            'status' => ['sometimes', Rule::in(['draft', 'scheduled'])],
            'scheduled_at' => 'nullable|date',
        ]);

        $post->update($validated);

        return $this->success($post, 'Пост обновлён');
    }

    /**
     * Delete post
     */
    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return $this->success(null, 'Пост удалён');
    }

    /**
     * Publish post immediately
     */
    public function publish(Post $post): JsonResponse
    {
        if ($post->isPublished()) {
            return $this->error('Пост уже опубликован');
        }

        PublishPost::dispatch($post);

        return $this->success($post, 'Публикация запущена');
    }

    /**
     * Bulk generate posts for sites
     */
    public function bulkGenerate(Request $request): JsonResponse
    {
        $request->validate([
            'site_ids' => 'required|array|min:1',
            'site_ids.*' => 'integer|exists:sites,id',
            'type' => ['required', Rule::in(array_keys(Post::getTypes()))],
            'schedule' => 'boolean', // Schedule or publish immediately
        ]);

        $sites = Site::whereIn('id', $request->site_ids)
            ->where('status', Site::STATUS_PUBLISHED)
            ->get();

        $queued = 0;

        foreach ($sites as $site) {
            GeneratePost::dispatch($site, $request->type, $request->boolean('schedule', true));
            $queued++;
        }

        return $this->success([
            'queued' => $queued,
        ], 'Генерация постов запущена');
    }

    /**
     * Get autopost settings for site
     */
    public function getAutopostSettings(Site $site): JsonResponse
    {
        $settings = $site->autopostSettings ?? new AutopostSetting([
            'site_id' => $site->id,
            'is_enabled' => false,
            'frequency' => AutopostSetting::FREQUENCY_EVERY_3_DAYS,
            'post_types' => [Post::TYPE_ARTICLE],
            'time_range_start' => '09:00',
            'time_range_end' => '18:00',
        ]);

        return $this->success($settings);
    }

    /**
     * Update autopost settings for site
     */
    public function updateAutopostSettings(Request $request, Site $site): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => 'required|boolean',
            'frequency' => ['required', Rule::in(array_keys(AutopostSetting::getFrequencies()))],
            'frequency_variance' => 'nullable|integer|min:0|max:7',
            'post_types' => 'required|array|min:1',
            'post_types.*' => Rule::in(array_keys(Post::getTypes())),
            'time_range_start' => 'required|date_format:H:i',
            'time_range_end' => 'required|date_format:H:i|after:time_range_start',
            'weekdays_only' => 'boolean',
            'custom_prompts' => 'nullable|array',
        ]);

        $settings = AutopostSetting::updateOrCreate(
            ['site_id' => $site->id],
            $validated
        );

        // Schedule next post if enabled and not scheduled
        if ($settings->is_enabled && !$settings->next_post_at) {
            $settings->scheduleNextPost();
        }

        return $this->success($settings, 'Настройки автопостинга обновлены');
    }

    /**
     * Bulk enable autopost
     */
    public function bulkEnableAutopost(Request $request): JsonResponse
    {
        $request->validate([
            'site_ids' => 'required|array|min:1',
            'site_ids.*' => 'integer|exists:sites,id',
            'frequency' => ['required', Rule::in(array_keys(AutopostSetting::getFrequencies()))],
            'post_types' => 'required|array|min:1',
        ]);

        $enabled = 0;

        foreach ($request->site_ids as $siteId) {
            $settings = AutopostSetting::updateOrCreate(
                ['site_id' => $siteId],
                [
                    'is_enabled' => true,
                    'frequency' => $request->frequency,
                    'post_types' => $request->post_types,
                    'time_range_start' => '09:00',
                    'time_range_end' => '18:00',
                ]
            );

            if (!$settings->next_post_at) {
                $settings->scheduleNextPost();
            }

            $enabled++;
        }

        return $this->success([
            'enabled' => $enabled,
        ], 'Автопостинг включён');
    }

    /**
     * Bulk disable autopost
     */
    public function bulkDisableAutopost(Request $request): JsonResponse
    {
        $request->validate([
            'site_ids' => 'required|array|min:1',
            'site_ids.*' => 'integer|exists:sites,id',
        ]);

        $disabled = AutopostSetting::whereIn('site_id', $request->site_ids)
            ->update(['is_enabled' => false]);

        return $this->success([
            'disabled' => $disabled,
        ], 'Автопостинг выключен');
    }

    /**
     * Get post statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Post::count(),
            'by_status' => Post::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_type' => Post::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'published_today' => Post::whereDate('published_at', today())->count(),
            'scheduled' => Post::scheduled()->count(),
            'autopost_enabled_sites' => AutopostSetting::where('is_enabled', true)->count(),
        ];

        return $this->success($stats);
    }

    /**
     * Get scheduled posts queue
     */
    public function scheduledQueue(): JsonResponse
    {
        $posts = Post::scheduled()
            ->with('site.domain')
            ->orderBy('scheduled_at')
            ->take(50)
            ->get();

        return $this->success($posts);
    }
}
