<?php

declare(strict_types=1);

namespace App\Services\AI;

interface AiServiceInterface
{
    /**
     * Generate text completion
     * 
     * @param string $prompt The prompt to send
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @return string Generated text
     */
    public function complete(string $prompt, array $options = []): string;

    /**
     * Generate structured content (JSON)
     * 
     * @param string $prompt The prompt with JSON structure request
     * @param array $options Additional options
     * @return array Parsed JSON response
     */
    public function completeJson(string $prompt, array $options = []): array;

    /**
     * Check if service is available
     */
    public function isAvailable(): bool;

    /**
     * Get remaining quota
     */
    public function getRemainingQuota(): ?int;
}
