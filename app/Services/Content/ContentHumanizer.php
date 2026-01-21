<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\GlobalSetting;

class ContentHumanizer
{
    /**
     * Synonyms for common overused words
     */
    private array $synonyms = [
        'качественный' => ['превосходный', 'отличный', 'первоклассный', 'высококлассный', 'надёжный'],
        'профессиональный' => ['опытный', 'квалифицированный', 'экспертный', 'компетентный', 'мастерский'],
        'эффективный' => ['действенный', 'результативный', 'продуктивный', 'успешный', 'работающий'],
        'уникальный' => ['неповторимый', 'особенный', 'исключительный', 'единственный в своём роде', 'оригинальный'],
        'инновационный' => ['современный', 'передовой', 'новаторский', 'прогрессивный', 'революционный'],
        'оптимальный' => ['лучший', 'идеальный', 'наиболее подходящий', 'совершенный', 'безупречный'],
        'комплексный' => ['всесторонний', 'полный', 'целостный', 'системный', 'масштабный'],
        'индивидуальный' => ['персональный', 'личный', 'адресный', 'под ваши нужды'],
        'быстрый' => ['оперативный', 'скорый', 'стремительный', 'незамедлительный'],
        'гарантированный' => ['обеспеченный', 'надёжный', 'проверенный', 'подтверждённый'],
    ];

    /**
     * Humanize all content recursively
     */
    public function humanizeContent(array $content): array
    {
        $level = GlobalSetting::get('content_variation_level', 3);
        
        if ($level === 0) {
            return $content;
        }

        return $this->processArray($content, $level);
    }

    /**
     * Process array recursively
     */
    private function processArray(array $array, int $level): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->processArray($value, $level);
            } elseif (is_string($value) && mb_strlen($value) > 20) {
                $array[$key] = $this->humanizeText($value, $level);
            }
        }
        return $array;
    }

    /**
     * Humanize a single text
     */
    public function humanizeText(string $text, int $level = 3): string
    {
        if (empty($text) || mb_strlen($text) < 20) {
            return $text;
        }

        // Level 1: Basic synonym replacement
        if ($level >= 1) {
            $text = $this->replaceSynonyms($text);
        }

        // Level 2: Add slight variations to punctuation and spacing
        if ($level >= 2) {
            $text = $this->varyPunctuation($text);
        }

        // Level 3: Add occasional imperfections
        if ($level >= 3) {
            $text = $this->addNaturalImperfections($text);
        }

        return $text;
    }

    /**
     * Replace overused words with synonyms
     */
    private function replaceSynonyms(string $text): string
    {
        foreach ($this->synonyms as $word => $alternatives) {
            // Only replace sometimes (70% chance)
            if (rand(1, 100) <= 70 && mb_stripos($text, $word) !== false) {
                $replacement = $alternatives[array_rand($alternatives)];
                // Replace only first occurrence
                $text = preg_replace('/\b' . preg_quote($word, '/') . '\b/iu', $replacement, $text, 1);
            }
        }
        return $text;
    }

    /**
     * Vary punctuation slightly
     */
    private function varyPunctuation(string $text): string
    {
        // Sometimes replace ". " with ".\n" for paragraph breaks (10% chance per sentence)
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        if (count($sentences) > 3 && rand(1, 100) <= 30) {
            $breakPoint = rand(2, count($sentences) - 1);
            $sentences[$breakPoint - 1] .= "\n\n";
            $text = implode(' ', $sentences);
        }

        // Sometimes add em-dash instead of comma (5% chance)
        if (rand(1, 100) <= 5) {
            $text = preg_replace('/,\s/', ' — ', $text, 1);
        }

        return $text;
    }

    /**
     * Add natural imperfections that humans make
     */
    private function addNaturalImperfections(string $text): string
    {
        // Occasionally vary sentence length by splitting or merging
        // This is subtle - only affects structure, not meaning
        
        // Sometimes use "который/которая" instead of relative clauses
        // Sometimes use shorter sentences
        
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($sentences as $i => $sentence) {
            // Very long sentences (>150 chars) - maybe split
            if (mb_strlen($sentence) > 150 && rand(1, 100) <= 30) {
                // Find a comma to split at
                $commaPos = mb_strpos($sentence, ', ');
                if ($commaPos !== false && $commaPos > 30) {
                    $part1 = mb_substr($sentence, 0, $commaPos + 1);
                    $part2 = mb_ucfirst(trim(mb_substr($sentence, $commaPos + 2)));
                    $sentences[$i] = $part1 . ' ' . $part2;
                }
            }
        }

        return implode(' ', $sentences);
    }

    /**
     * Check text for AI-like patterns
     */
    public function detectAiPatterns(string $text): array
    {
        $patterns = [];

        // Check for repetitive sentence starters
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $starters = [];
        foreach ($sentences as $sentence) {
            $words = explode(' ', trim($sentence));
            if (count($words) >= 2) {
                $starter = $words[0] . ' ' . $words[1];
                $starters[$starter] = ($starters[$starter] ?? 0) + 1;
            }
        }
        foreach ($starters as $starter => $count) {
            if ($count > 2) {
                $patterns[] = "Repetitive starter: '{$starter}' used {$count} times";
            }
        }

        // Check for overused corporate buzzwords
        $buzzwords = ['инновационный', 'синергия', 'оптимизация', 'комплексный подход', 'индивидуальный подход'];
        $buzzwordCount = 0;
        foreach ($buzzwords as $word) {
            $buzzwordCount += mb_substr_count(mb_strtolower($text), $word);
        }
        if ($buzzwordCount > 3) {
            $patterns[] = "High buzzword density: {$buzzwordCount} instances";
        }

        // Check for perfect parallel structure (AI tendency)
        if (preg_match_all('/(?:^|\.\s)(\w+)\s+—\s+/u', $text, $matches)) {
            if (count($matches[0]) > 3) {
                $patterns[] = "Repetitive parallel structure detected";
            }
        }

        return $patterns;
    }

    /**
     * Get humanization score (0-100)
     */
    public function getHumanScore(string $text): int
    {
        $patterns = $this->detectAiPatterns($text);
        $score = 100;

        // Deduct points for each detected pattern
        $score -= count($patterns) * 15;

        // Check sentence variety
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $lengths = array_map('mb_strlen', $sentences);
        
        if (count($lengths) > 3) {
            $variance = $this->calculateVariance($lengths);
            // Low variance = AI-like uniformity
            if ($variance < 200) {
                $score -= 10;
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate variance of array
     */
    private function calculateVariance(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0;
        }
        
        $mean = array_sum($values) / $count;
        $squaredDiffs = array_map(fn($x) => pow($x - $mean, 2), $values);
        
        return array_sum($squaredDiffs) / $count;
    }
}

/**
 * Helper function for mb_ucfirst
 */
if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst(string $string): string
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    }
}
