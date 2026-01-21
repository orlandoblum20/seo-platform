<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    /**
     * List all templates
     */
    public function index(Request $request): JsonResponse
    {
        $query = Template::withCount('sites');

        if ($request->has('type')) {
            $query->type($request->type);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $templates = $query->ordered()->get();

        return $this->success($templates);
    }

    /**
     * Get single template
     */
    public function show(Template $template): JsonResponse
    {
        $template->loadCount('sites');
        
        return $this->success($template);
    }

    /**
     * Create template
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:templates,slug',
            'type' => ['required', Rule::in(array_keys(Template::getTypes()))],
            'description' => 'nullable|string',
            'preview_image' => 'nullable|string|max:500',
            'structure' => 'required|array',
            'structure.pages' => 'required|array',
            'default_prompts' => 'nullable|array',
            'color_schemes' => 'nullable|array',
            'seo_settings' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Ensure unique slug
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (Template::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter++;
        }

        $template = Template::create($validated);

        return $this->success($template, 'Шаблон создан', 201);
    }

    /**
     * Update template
     */
    public function update(Request $request, Template $template): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('templates')->ignore($template->id)],
            'type' => ['sometimes', Rule::in(array_keys(Template::getTypes()))],
            'description' => 'nullable|string',
            'preview_image' => 'nullable|string|max:500',
            'structure' => 'sometimes|array',
            'default_prompts' => 'nullable|array',
            'color_schemes' => 'nullable|array',
            'seo_settings' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $template->update($validated);

        return $this->success($template, 'Шаблон обновлён');
    }

    /**
     * Delete template
     */
    public function destroy(Template $template): JsonResponse
    {
        // Check if template is used by sites
        if ($template->sites()->exists()) {
            return $this->error('Нельзя удалить шаблон, который используется сайтами');
        }

        $template->delete();

        return $this->success(null, 'Шаблон удалён');
    }

    /**
     * Duplicate template
     */
    public function duplicate(Template $template): JsonResponse
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (копия)';
        $newTemplate->slug = $template->slug . '-copy-' . Str::random(5);
        $newTemplate->save();

        return $this->success($newTemplate, 'Шаблон скопирован', 201);
    }

    /**
     * Reorder templates
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:templates,id',
        ]);

        foreach ($request->order as $index => $templateId) {
            Template::where('id', $templateId)->update(['sort_order' => $index]);
        }

        return $this->success(null, 'Порядок обновлён');
    }

    /**
     * Get template types
     */
    public function types(): JsonResponse
    {
        return $this->success(Template::getTypes());
    }
}
