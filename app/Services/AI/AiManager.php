<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiSetting;
use Exception;

class AiManager
{
    private ?AiServiceInterface $service = null;

    /**
     * Get AI service instance
     */
    public function getService(?string $provider = null): AiServiceInterface
    {
        $setting = $provider 
            ? AiSetting::where('provider', $provider)->where('is_active', true)->first()
            : AiSetting::getDefault();

        if (!$setting) {
            throw new Exception('No AI provider configured');
        }

        return $this->createService($setting);
    }

    /**
     * Get available service (with fallback)
     */
    public function getAvailableService(): AiServiceInterface
    {
        // Try default first
        $default = AiSetting::getDefault();
        if ($default && $default->canMakeRequest()) {
            return $this->createService($default);
        }

        // Try any available
        $available = AiSetting::available()->first();
        if ($available) {
            return $this->createService($available);
        }

        throw new Exception('No AI service available');
    }

    /**
     * Create service from setting
     */
    private function createService(AiSetting $setting): AiServiceInterface
    {
        return match ($setting->provider) {
            AiSetting::PROVIDER_ANTHROPIC => new AnthropicService($setting),
            AiSetting::PROVIDER_OPENAI => new OpenAIService($setting),
            AiSetting::PROVIDER_DEEPSEEK => new DeepSeekService($setting),
            default => throw new Exception("Unknown AI provider: {$setting->provider}"),
        };
    }

    /**
     * Generate text with automatic provider selection
     */
    public function generate(string $prompt, array $options = []): string
    {
        $service = $this->getAvailableService();
        return $service->complete($prompt, $options);
    }

    /**
     * Generate JSON with automatic provider selection
     */
    public function generateJson(string $prompt, array $options = []): array
    {
        $service = $this->getAvailableService();
        return $service->completeJson($prompt, $options);
    }

    /**
     * Generate with role-specific system prompt
     */
    public function generateAs(string $role, string $prompt, array $options = []): string
    {
        $service = $this->getAvailableService();
        
        if (method_exists($service, 'generateAs')) {
            return $service->generateAs($role, $prompt, $options);
        }

        return $service->complete($prompt, $options);
    }

    /**
     * Check if any AI service is available
     */
    public function isAvailable(): bool
    {
        return AiSetting::available()->exists();
    }

    /**
     * Get status of all providers
     */
    public function getProvidersStatus(): array
    {
        $settings = AiSetting::all();
        $status = [];

        foreach ($settings as $setting) {
            $status[] = [
                'id' => $setting->id,
                'name' => $setting->name,
                'provider' => $setting->provider,
                'model' => $setting->model,
                'is_active' => $setting->is_active,
                'is_default' => $setting->is_default,
                'can_make_request' => $setting->canMakeRequest(),
                'requests_today' => $setting->requests_today,
                'daily_limit' => $setting->daily_limit,
                'remaining_quota' => $setting->daily_limit 
                    ? $setting->daily_limit - $setting->requests_today 
                    : null,
            ];
        }

        return $status;
    }
}
