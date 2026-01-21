<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AnthropicService implements AiServiceInterface
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

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
        // Add JSON instruction to prompt
        $jsonPrompt = $prompt . "\n\nIMPORTANT: Respond with valid JSON only, no additional text or markdown.";

        $response = $this->complete($jsonPrompt, $options);

        // Clean response (remove markdown code blocks if present)
        $response = preg_replace('/^```json\s*/', '', $response);
        $response = preg_replace('/\s*```$/', '', $response);
        $response = trim($response);

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse AI JSON response', [
                'response' => $response,
                'error' => json_last_error_msg(),
            ]);
            throw new Exception('Failed to parse AI response as JSON: ' . json_last_error_msg());
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
        $systemPrompt = $options['system'] ?? null;

        $messages = [
            ['role' => 'user', 'content' => $prompt],
        ];

        $payload = [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => $messages,
        ];

        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => self::API_VERSION,
                'Content-Type' => 'application/json',
            ])
            ->timeout(120)
            ->post(self::API_URL, $payload);

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? $response->body();
                throw new Exception("Anthropic API error: {$error}");
            }

            $result = $response->json();

            // Extract text from response
            $content = $result['content'] ?? [];
            $text = '';
            foreach ($content as $block) {
                if ($block['type'] === 'text') {
                    $text .= $block['text'];
                }
            }

            return $text;

        } catch (Exception $e) {
            Log::error('Anthropic API request failed', [
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
            'seo_expert' => 'You are an expert SEO copywriter with 15 years of experience. You write engaging, keyword-optimized content that ranks well in search engines while remaining natural and valuable to readers. You understand semantic SEO, user intent, and modern search algorithms.',
            
            'business_writer' => 'You are a professional business content writer. You create clear, professional, and persuasive content for corporate websites. Your writing is formal yet approachable, focused on benefits and value propositions.',
            
            'blog_author' => 'You are a skilled blog writer who creates engaging, informative articles. Your writing style is conversational yet professional. You use storytelling, examples, and practical advice to keep readers engaged.',
            
            'news_reporter' => 'You are a concise news writer. You write factual, timely news articles with attention-grabbing headlines. Your style is objective and informative.',
            
            'faq_specialist' => 'You are a customer service expert who creates clear, helpful FAQ content. You anticipate user questions and provide comprehensive yet concise answers.',
        ];

        $options['system'] = $systemPrompts[$role] ?? $systemPrompts['seo_expert'];

        return $this->complete($prompt, $options);
    }

    /**
     * Generate multiple variations
     */
    public function generateVariations(string $prompt, int $count = 3, array $options = []): array
    {
        $variations = [];
        $temperatures = [0.5, 0.7, 0.9]; // Different temperatures for variety

        for ($i = 0; $i < $count; $i++) {
            $opts = array_merge($options, [
                'temperature' => $temperatures[$i % count($temperatures)],
            ]);
            
            $variationPrompt = $prompt . "\n\nVariation #{$i} - please create a unique version.";
            $variations[] = $this->complete($variationPrompt, $opts);
        }

        return $variations;
    }
}
