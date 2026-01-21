<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Site;
use App\Models\Template;
use App\Services\AI\AiManager;
use Illuminate\Support\Facades\Log;
use Exception;

class ContentGenerator
{
    private AiManager $aiManager;
    private PromptBuilder $promptBuilder;
    private ContentHumanizer $humanizer;

    public function __construct()
    {
        $this->aiManager = new AiManager();
        $this->promptBuilder = new PromptBuilder();
        $this->humanizer = new ContentHumanizer();
    }

    /**
     * Generate all content for a site
     */
    public function generateSiteContent(Site $site): array
    {
        $template = $site->template;
        $keywords = $site->keywords ?? [];
        $niche = $site->niche ?? 'general business';

        Log::info('Starting content generation', [
            'site_id' => $site->id,
            'template' => $template->slug,
            'keywords_count' => count($keywords),
        ]);

        $content = [];

        // Generate SEO metadata first
        $seoData = $this->generateSeoMetadata($niche, $keywords, $template);
        $site->update([
            'seo_title' => $seoData['title'],
            'seo_description' => $seoData['description'],
            'seo_keywords' => implode(', ', $seoData['keywords']),
        ]);

        // Generate content for each page
        $pages = $template->getPages();
        foreach ($pages as $pageName => $pageConfig) {
            $content[$pageName] = $this->generatePageContent(
                $pageName,
                $pageConfig,
                $niche,
                $keywords,
                $template
            );
        }

        // Generate FAQ if template supports it
        if ($template->structure['features']['faq'] ?? true) {
            $content['faq'] = $this->generateFaq($niche, $keywords);
        }

        // Humanize all text content
        $content = $this->humanizer->humanizeContent($content);

        return $content;
    }

    /**
     * Generate SEO metadata
     */
    public function generateSeoMetadata(string $niche, array $keywords, Template $template): array
    {
        $mainKeyword = $keywords[0] ?? $niche;
        
        $prompt = $this->promptBuilder->buildSeoPrompt($niche, $keywords, $template->type);

        $response = $this->aiManager->generateJson($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 500,
        ]);

        return [
            'title' => $this->truncate($response['title'] ?? $mainKeyword, 60),
            'description' => $this->truncate($response['description'] ?? '', 160),
            'keywords' => array_slice($response['keywords'] ?? $keywords, 0, 10),
            'h1' => $response['h1'] ?? $mainKeyword,
        ];
    }

    /**
     * Generate content for a single page
     */
    public function generatePageContent(
        string $pageName,
        array $pageConfig,
        string $niche,
        array $keywords,
        Template $template
    ): array {
        $sections = $pageConfig['sections'] ?? [];
        $pageContent = [];

        foreach ($sections as $sectionName => $sectionConfig) {
            $prompt = $this->promptBuilder->buildSectionPrompt(
                $sectionName,
                $sectionConfig,
                $niche,
                $keywords,
                $template->getPromptForSection($sectionName)
            );

            try {
                $sectionContent = $this->aiManager->generateJson($prompt, [
                    'temperature' => 0.8,
                    'max_tokens' => 1500,
                ]);

                $pageContent[$sectionName] = $sectionContent;

            } catch (Exception $e) {
                Log::error('Failed to generate section content', [
                    'page' => $pageName,
                    'section' => $sectionName,
                    'error' => $e->getMessage(),
                ]);

                // Use fallback content
                $pageContent[$sectionName] = $this->getFallbackContent($sectionName, $niche);
            }
        }

        return $pageContent;
    }

    /**
     * Generate FAQ content
     */
    public function generateFaq(string $niche, array $keywords, int $count = 6): array
    {
        $prompt = $this->promptBuilder->buildFaqPrompt($niche, $keywords, $count);

        $response = $this->aiManager->generateJson($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        return $response['faq'] ?? [];
    }

    /**
     * Generate blog post content
     */
    public function generateBlogPost(Site $site, string $type = 'article'): array
    {
        $keywords = $site->keywords ?? [];
        $niche = $site->niche ?? 'general';

        $prompt = $this->promptBuilder->buildBlogPostPrompt(
            $niche,
            $keywords,
            $type
        );

        $response = $this->aiManager->generateAs('blog_author', $prompt, [
            'temperature' => 0.85,
            'max_tokens' => 3000,
        ]);

        // Parse response - expect JSON
        $jsonPrompt = $prompt . "\n\nRespond with JSON containing: title, slug, excerpt, content, seo_title, seo_description";
        
        $postData = $this->aiManager->generateJson($jsonPrompt, [
            'temperature' => 0.85,
            'max_tokens' => 3000,
        ]);

        // Humanize the content
        $postData['content'] = $this->humanizer->humanizeText($postData['content'] ?? '');
        $postData['excerpt'] = $this->humanizer->humanizeText($postData['excerpt'] ?? '');

        return $postData;
    }

    /**
     * Regenerate specific section
     */
    public function regenerateSection(
        Site $site,
        string $pageName,
        string $sectionName
    ): array {
        $template = $site->template;
        $pages = $template->getPages();
        $sectionConfig = $pages[$pageName]['sections'][$sectionName] ?? [];

        $prompt = $this->promptBuilder->buildSectionPrompt(
            $sectionName,
            $sectionConfig,
            $site->niche ?? 'general',
            $site->keywords ?? [],
            $template->getPromptForSection($sectionName)
        );

        // Use higher temperature for variation
        $content = $this->aiManager->generateJson($prompt, [
            'temperature' => 0.9,
            'max_tokens' => 1500,
        ]);

        return $this->humanizer->humanizeContent([$sectionName => $content])[$sectionName];
    }

    /**
     * Get fallback content for failed generation
     */
    private function getFallbackContent(string $section, string $niche): array
    {
        $fallbacks = [
            'hero' => [
                'headline' => "Добро пожаловать в мир {$niche}",
                'subheadline' => 'Качественные решения для вашего бизнеса',
                'cta_text' => 'Узнать больше',
            ],
            'features' => [
                'title' => 'Наши преимущества',
                'items' => [
                    ['title' => 'Качество', 'description' => 'Высокое качество услуг'],
                    ['title' => 'Опыт', 'description' => 'Многолетний опыт работы'],
                    ['title' => 'Поддержка', 'description' => 'Профессиональная поддержка'],
                ],
            ],
            'about' => [
                'title' => 'О компании',
                'text' => "Мы специализируемся на {$niche} и предоставляем качественные услуги нашим клиентам.",
            ],
            'contacts' => [
                'title' => 'Контакты',
                'text' => 'Свяжитесь с нами для получения дополнительной информации.',
            ],
        ];

        return $fallbacks[$section] ?? ['title' => ucfirst($section), 'text' => ''];
    }

    /**
     * Truncate text to max length
     */
    private function truncate(string $text, int $maxLength): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength - 3) . '...';
    }
}
