<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenAIService implements AiServiceInterface
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private float $temperature;

    public function __construct(private AiSetting $setting)
    {
        $this->apiKey = $setting->api_key;
        $this->model = $setting->model;
        $this->maxTokens = $setting->max_tokens;
        $this->temperature = $setting->temperature;
    }

    public function complete(string $prompt, array $options = []): string
    {
        if (!$this->setting->canMakeRequest()) {
            throw new Exception('Rate limit exceeded or service unavailable');
        }

        $response = $this->makeRequest($prompt, $options);
        
        $this->setting->recordRequest();

        return $response;
    }

    public function completeJson(string $prompt, array $options = []): array
    {
        // OpenAI supports JSON mode
        $options['response_format'] = ['type' => 'json_object'];
        
        $jsonPrompt = $prompt . "\n\nRespond with valid JSON.";
        $response = $this->complete($jsonPrompt, $options);

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse OpenAI JSON response', [
                'response' => $response,
                'error' => json_last_error_msg(),
            ]);
            throw new Exception('Failed to parse AI response as JSON');
        }

        return $decoded;
    }

    public function isAvailable(): bool
    {
        return $this->setting->is_active && $this->setting->canMakeRequest();
    }

    public function getRemainingQuota(): ?int
    {
        if (!$this->setting->daily_limit) {
            return null;
        }
        return max(0, $this->setting->daily_limit - $this->setting->requests_today);
    }

    private function makeRequest(string $prompt, array $options = []): string
    {
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $temperature = $options['temperature'] ?? $this->temperature;
        $systemPrompt = $options['system'] ?? 'You are a helpful assistant.';

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $prompt],
        ];

        $payload = [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => $messages,
        ];

        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->timeout(120)
            ->post(self::API_URL, $payload);

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? $response->body();
                throw new Exception("OpenAI API error: {$error}");
            }

            $result = $response->json();

            return $result['choices'][0]['message']['content'] ?? '';

        } catch (Exception $e) {
            Log::error('OpenAI API request failed', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);
            throw $e;
        }
    }

    /**
     * Generate with system prompt for specific role
     */
    public function generateAs(string $role, string $prompt, array $options = []): string
    {
        $systemPrompts = [
            'seo_expert' => 'You are an expert SEO copywriter with 15 years of experience. You write engaging, keyword-optimized content that ranks well in search engines while remaining natural and valuable to readers.',
            
            'business_writer' => 'You are a professional business content writer creating clear, professional, and persuasive content for corporate websites.',
            
            'blog_author' => 'You are a skilled blog writer who creates engaging, informative articles with a conversational yet professional style.',
            
            'news_reporter' => 'You are a concise news writer creating factual, timely news articles with attention-grabbing headlines.',
            
            'faq_specialist' => 'You are a customer service expert creating clear, helpful FAQ content with comprehensive yet concise answers.',
        ];

        $options['system'] = $systemPrompts[$role] ?? $systemPrompts['seo_expert'];

        return $this->complete($prompt, $options);
    }
}
