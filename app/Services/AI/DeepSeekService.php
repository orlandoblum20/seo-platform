<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService implements AiServiceInterface
{
    protected AiSetting $setting;
    protected string $baseUrl = 'https://api.deepseek.com';

    public function __construct(AiSetting $setting)
    {
        $this->setting = $setting;
        
        if ($setting->api_endpoint) {
            $this->baseUrl = rtrim($setting->api_endpoint, '/');
        }
    }

    /**
     * Complete a prompt
     */
    public function complete(string $prompt, array $options = []): string
    {
        $response = $this->chat([
            ['role' => 'user', 'content' => $prompt]
        ], $options);

        return $response['content'] ?? '';
    }

    /**
     * Chat completion
     */
    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? $this->setting->model ?? 'deepseek-chat';
        $maxTokens = $options['max_tokens'] ?? $this->setting->max_tokens ?? 4096;
        $temperature = $options['temperature'] ?? $this->setting->temperature ?? 0.7;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        // Add system message if provided
        if (isset($options['system'])) {
            array_unshift($payload['messages'], [
                'role' => 'system',
                'content' => $options['system']
            ]);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->setting->api_key,
                'Content-Type' => 'application/json',
            ])
            ->timeout(120)
            ->post("{$this->baseUrl}/v1/chat/completions", $payload);

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? $response->body();
                Log::error('DeepSeek API error', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);
                throw new \Exception("DeepSeek API error: {$error}");
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            // Record the request
            $this->setting->recordRequest();

            return [
                'content' => $content,
                'model' => $data['model'] ?? $model,
                'usage' => [
                    'input_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'output_tokens' => $data['usage']['completion_tokens'] ?? 0,
                ],
                'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('DeepSeek API exception', [
                'message' => $e->getMessage(),
                'model' => $model,
            ]);
            throw $e;
        }
    }

    /**
     * Generate structured content
     */
    public function generateStructured(string $prompt, array $schema, array $options = []): array
    {
        $systemPrompt = "You are a helpful assistant that outputs valid JSON matching the provided schema. " .
                       "Only output the JSON, no additional text.";

        $userPrompt = $prompt . "\n\nRequired JSON schema:\n" . json_encode($schema, JSON_PRETTY_PRINT);

        $response = $this->chat([
            ['role' => 'user', 'content' => $userPrompt]
        ], array_merge($options, ['system' => $systemPrompt]));

        $content = $response['content'] ?? '';
        
        // Extract JSON from response
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode(trim($content), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse JSON response: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Check if service is available
     */
    public function isAvailable(): bool
    {
        return $this->setting->is_active && $this->setting->canMakeRequest();
    }

    /**
     * Get available models
     */
    public function getModels(): array
    {
        return [
            'deepseek-reasoner' => 'DeepSeek R1 (reasoning)',
            'deepseek-chat' => 'DeepSeek Chat (V3)',
        ];
    }

    /**
     * Get current model
     */
    public function getCurrentModel(): string
    {
        return $this->setting->model ?? 'deepseek-chat';
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats(): array
    {
        return [
            'requests_today' => $this->setting->requests_today,
            'daily_limit' => $this->setting->daily_limit,
            'last_request_at' => $this->setting->last_request_at?->toDateTimeString(),
        ];
    }
}
