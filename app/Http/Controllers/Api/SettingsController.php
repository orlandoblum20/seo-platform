<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlobalSetting;
use App\Models\AiSetting;
use App\Services\AI\AiManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Get all settings
     */
    public function index(): JsonResponse
    {
        $settings = GlobalSetting::all()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getCastedValue()];
        });

        return $this->success($settings);
    }

    /**
     * Get settings by group
     */
    public function getGroup(string $group): JsonResponse
    {
        $settings = GlobalSetting::getGroup($group);

        return $this->success($settings);
    }

    /**
     * Update settings
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            GlobalSetting::set($key, $value);
        }

        return $this->success(null, 'Настройки сохранены');
    }

    /**
     * Get AI settings
     */
    public function getAiSettings(): JsonResponse
    {
        $settings = AiSetting::all();
        $aiManager = new AiManager();

        return $this->success([
            'providers' => $settings,
            'status' => $aiManager->getProvidersStatus(),
            'available_providers' => AiSetting::getProviders(),
        ]);
    }

    /**
     * Create AI setting
     */
    public function createAiSetting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', Rule::in(array_keys(AiSetting::getProviders()))],
            'name' => 'required|string|max:255',
            'api_key' => 'required|string',
            'model' => 'required|string',
            'max_tokens' => 'nullable|integer|min:100|max:100000',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'rate_limit' => 'nullable|integer|min:1',
            'daily_limit' => 'nullable|integer|min:1',
        ]);

        $setting = AiSetting::create($validated);

        return $this->success($setting, 'AI провайдер добавлен', 201);
    }

    /**
     * Update AI setting
     */
    public function updateAiSetting(Request $request, AiSetting $aiSetting): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'api_key' => 'sometimes|string',
            'model' => 'sometimes|string',
            'max_tokens' => 'nullable|integer|min:100|max:100000',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'rate_limit' => 'nullable|integer|min:1',
            'daily_limit' => 'nullable|integer|min:1',
        ]);

        $aiSetting->update($validated);

        return $this->success($aiSetting, 'AI провайдер обновлён');
    }

    /**
     * Delete AI setting
     */
    public function deleteAiSetting(AiSetting $aiSetting): JsonResponse
    {
        if ($aiSetting->is_default && AiSetting::where('provider', $aiSetting->provider)->count() === 1) {
            return $this->error('Нельзя удалить единственный провайдер');
        }

        $aiSetting->delete();

        return $this->success(null, 'AI провайдер удалён');
    }

    /**
     * Test AI setting
     */
    public function testAiSetting(AiSetting $aiSetting): JsonResponse
    {
        try {
            $aiManager = new AiManager();
            $service = $aiManager->getService($aiSetting->provider);

            if (!$service->isAvailable()) {
                return $this->error('Сервис недоступен (лимит или неактивен)');
            }

            $response = $service->complete('Ответь одним словом: работает', [
                'max_tokens' => 10,
            ]);

            return $this->success([
                'working' => true,
                'response' => $response,
            ], 'API работает');

        } catch (\Exception $e) {
            return $this->error('Ошибка API: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Set default AI provider
     */
    public function setDefaultAi(AiSetting $aiSetting): JsonResponse
    {
        if (!$aiSetting->is_active) {
            return $this->error('Нельзя сделать неактивный провайдер основным');
        }

        $aiSetting->update(['is_default' => true]);

        return $this->success($aiSetting, 'Провайдер назначен основным');
    }

    /**
     * Get Keitaro settings
     */
    public function getKeitaroSettings(): JsonResponse
    {
        return $this->success([
            'enabled' => GlobalSetting::get('keitaro_enabled', false),
            'url' => GlobalSetting::get('keitaro_url', ''),
            'campaign_id' => GlobalSetting::get('keitaro_campaign_id', ''),
            'excluded_domains' => GlobalSetting::get('keitaro_excluded_domains', []),
        ]);
    }

    /**
     * Update Keitaro settings
     */
    public function updateKeitaroSettings(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'url' => 'required_if:enabled,true|nullable|url',
            'campaign_id' => 'required_if:enabled,true|nullable|string',
            'excluded_domains' => 'nullable|array',
        ]);

        GlobalSetting::set('keitaro_enabled', $request->enabled, 'boolean', 'keitaro');
        GlobalSetting::set('keitaro_url', $request->url ?? '', 'string', 'keitaro');
        GlobalSetting::set('keitaro_campaign_id', $request->campaign_id ?? '', 'string', 'keitaro');
        GlobalSetting::set('keitaro_excluded_domains', $request->excluded_domains ?? [], 'array', 'keitaro');

        return $this->success(null, 'Настройки Keitaro сохранены');
    }

    /**
     * Get analytics settings
     */
    public function getAnalyticsSettings(): JsonResponse
    {
        return $this->success([
            'yandex_metrika' => GlobalSetting::get('global_yandex_metrika', ''),
            'google_analytics' => GlobalSetting::get('global_google_analytics', ''),
            'google_tag_manager' => GlobalSetting::get('global_gtm', ''),
            'custom_scripts' => GlobalSetting::get('global_custom_scripts', ''),
        ]);
    }

    /**
     * Update analytics settings
     */
    public function updateAnalyticsSettings(Request $request): JsonResponse
    {
        $request->validate([
            'yandex_metrika' => 'nullable|string',
            'google_analytics' => 'nullable|string',
            'google_tag_manager' => 'nullable|string',
            'custom_scripts' => 'nullable|string',
        ]);

        GlobalSetting::set('global_yandex_metrika', $request->yandex_metrika ?? '', 'string', 'analytics');
        GlobalSetting::set('global_google_analytics', $request->google_analytics ?? '', 'string', 'analytics');
        GlobalSetting::set('global_gtm', $request->google_tag_manager ?? '', 'string', 'analytics');
        GlobalSetting::set('global_custom_scripts', $request->custom_scripts ?? '', 'string', 'analytics');

        return $this->success(null, 'Настройки аналитики сохранены');
    }

    /**
     * Get available models for provider
     */
    public function getModels(string $provider): JsonResponse
    {
        $models = AiSetting::getModelsByProvider($provider);

        return $this->success($models);
    }
}
