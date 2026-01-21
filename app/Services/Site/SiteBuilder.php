<?php

declare(strict_types=1);

namespace App\Services\Site;

use App\Models\Site;
use App\Models\Post;
use App\Models\GlobalSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class SiteBuilder
{
    private string $buildBasePath;
    private string $templatesPath;

    public function __construct()
    {
        $this->buildBasePath = storage_path('app/sites/builds');
        $this->templatesPath = resource_path('views/templates');
    }

    /**
     * Build complete site
     */
    public function build(Site $site): string
    {
        $buildPath = $this->getBuildPath($site);
        
        // Clean previous build
        if (File::exists($buildPath)) {
            File::deleteDirectory($buildPath);
        }
        File::makeDirectory($buildPath, 0755, true);

        // Build pages
        $this->buildPages($site, $buildPath);

        // Build assets
        $this->buildAssets($site, $buildPath);

        // Build robots.txt
        $this->buildRobotsTxt($site, $buildPath);

        // Build sitemap
        $this->buildSitemap($site, $buildPath);

        // Build Keitaro inject if enabled
        if ($site->keitaro_enabled && GlobalSetting::get('keitaro_enabled', false)) {
            $this->buildKeitaroInject($site, $buildPath);
        }

        return $buildPath;
    }

    /**
     * Build all pages
     */
    private function buildPages(Site $site, string $buildPath): void
    {
        $template = $site->template;
        $pages = $template->getPages();

        foreach ($pages as $pageName => $pageConfig) {
            $html = $this->renderPage($site, $pageName, $pageConfig);
            
            $filename = $pageName === 'home' ? 'index.html' : "{$pageName}.html";
            File::put("{$buildPath}/{$filename}", $html);
        }

        // Build blog/news index if applicable
        if ($template->hasBlog()) {
            $this->buildBlogIndex($site, $buildPath);
        }

        // Build existing posts
        $posts = $site->posts()->published()->get();
        foreach ($posts as $post) {
            $postHtml = $this->buildPostPage($post);
            $postDir = $this->getPostDirectory($post);
            File::makeDirectory("{$buildPath}/{$postDir}", 0755, true);
            File::put("{$buildPath}/{$postDir}/{$post->slug}.html", $postHtml);
        }
    }

    /**
     * Render a page
     */
    private function renderPage(Site $site, string $pageName, array $pageConfig): string
    {
        $template = $site->template;
        $content = $site->content[$pageName] ?? [];

        $data = [
            'site' => $site,
            'page' => $pageName,
            'content' => $content,
            'template' => $template,
            'colors' => $site->color_scheme ?? $template->getDefaultColorScheme(),
            'seo' => [
                'title' => $site->seo_title ?? $site->title,
                'description' => $site->seo_description ?? '',
                'keywords' => $site->seo_keywords ?? '',
            ],
            'analytics' => $this->getAnalyticsCodes($site),
            'backlinks' => $this->getBacklinksHtml($site),
            'schema' => $this->generateSchemaMarkup($site, $pageName),
        ];

        // Try template-specific view first, then fallback to generic
        $viewName = "templates.{$template->slug}.{$pageName}";
        if (!View::exists($viewName)) {
            $viewName = "templates.{$template->type}.{$pageName}";
        }
        if (!View::exists($viewName)) {
            $viewName = "templates.base.page";
        }

        return View::make($viewName, $data)->render();
    }

    /**
     * Build post page
     */
    public function buildPostPage(Post $post): string
    {
        $site = $post->site;
        $template = $site->template;

        $data = [
            'site' => $site,
            'post' => $post,
            'template' => $template,
            'colors' => $site->color_scheme ?? $template->getDefaultColorScheme(),
            'seo' => [
                'title' => $post->seo_title ?? $post->title,
                'description' => $post->seo_description ?? $post->excerpt,
            ],
            'analytics' => $this->getAnalyticsCodes($site),
            'schema' => $this->generatePostSchema($post),
        ];

        $viewName = "templates.{$template->slug}.post";
        if (!View::exists($viewName)) {
            $viewName = "templates.{$template->type}.post";
        }
        if (!View::exists($viewName)) {
            $viewName = "templates.base.post";
        }

        return View::make($viewName, $data)->render();
    }

    /**
     * Build blog index page
     */
    private function buildBlogIndex(Site $site, string $buildPath): void
    {
        $posts = $site->posts()->published()->latest('published_at')->get();
        $template = $site->template;

        $data = [
            'site' => $site,
            'posts' => $posts,
            'template' => $template,
            'colors' => $site->color_scheme ?? $template->getDefaultColorScheme(),
            'seo' => [
                'title' => 'Блог - ' . $site->title,
                'description' => 'Статьи и новости от ' . $site->title,
            ],
            'analytics' => $this->getAnalyticsCodes($site),
        ];

        $viewName = "templates.{$template->slug}.blog-index";
        if (!View::exists($viewName)) {
            $viewName = "templates.base.blog-index";
        }

        $html = View::make($viewName, $data)->render();

        File::makeDirectory("{$buildPath}/blog", 0755, true);
        File::put("{$buildPath}/blog/index.html", $html);
    }

    /**
     * Build static assets
     */
    private function buildAssets(Site $site, string $buildPath): void
    {
        // Create assets directory
        File::makeDirectory("{$buildPath}/assets/css", 0755, true);
        File::makeDirectory("{$buildPath}/assets/js", 0755, true);
        File::makeDirectory("{$buildPath}/assets/images", 0755, true);

        // Generate custom CSS with color scheme
        $css = $this->generateCustomCss($site);
        File::put("{$buildPath}/assets/css/style.css", $css);

        // Copy template assets
        $templateAssetsPath = resource_path("views/templates/{$site->template->slug}/assets");
        if (File::exists($templateAssetsPath)) {
            File::copyDirectory($templateAssetsPath, "{$buildPath}/assets");
        }

        // Add custom CSS if any
        if ($site->custom_css) {
            File::append("{$buildPath}/assets/css/style.css", "\n/* Custom CSS */\n" . $site->custom_css);
        }

        // Add custom JS if any
        if ($site->custom_js) {
            File::put("{$buildPath}/assets/js/custom.js", $site->custom_js);
        }
    }

    /**
     * Generate custom CSS with color scheme
     */
    private function generateCustomCss(Site $site): string
    {
        $colors = $site->color_scheme ?? $site->template->getDefaultColorScheme();

        return ":root {
    --color-primary: {$colors['primary']};
    --color-secondary: {$colors['secondary']};
    --color-accent: {$colors['accent']};
    --color-background: {$colors['background']};
    --color-text: {$colors['text']};
    --color-muted: {$colors['muted']};
}";
    }

    /**
     * Build robots.txt
     */
    private function buildRobotsTxt(Site $site, string $buildPath): void
    {
        $domain = $site->domain->domain;
        $robots = GlobalSetting::get('default_robots_txt', "User-agent: *\nAllow: /");
        $robots .= "\n\nSitemap: https://{$domain}/sitemap.xml";

        File::put("{$buildPath}/robots.txt", $robots);
    }

    /**
     * Build sitemap.xml
     */
    private function buildSitemap(Site $site, string $buildPath): void
    {
        $domain = $site->domain->domain;
        $baseUrl = "https://{$domain}";
        $template = $site->template;
        $pages = array_keys($template->getPages());

        $urls = [];

        // Add main pages
        foreach ($pages as $page) {
            $loc = $page === 'home' ? $baseUrl : "{$baseUrl}/{$page}.html";
            $urls[] = [
                'loc' => $loc,
                'lastmod' => $site->updated_at->toW3cString(),
                'changefreq' => $page === 'home' ? 'daily' : 'weekly',
                'priority' => $page === 'home' ? '1.0' : '0.8',
            ];
        }

        // Add blog index
        if ($template->hasBlog()) {
            $urls[] = [
                'loc' => "{$baseUrl}/blog/",
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ];
        }

        // Add posts
        $posts = $site->posts()->published()->get();
        foreach ($posts as $post) {
            $postDir = $this->getPostDirectory($post);
            $urls[] = [
                'loc' => "{$baseUrl}/{$postDir}/{$post->slug}.html",
                'lastmod' => $post->updated_at->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        // Generate XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$url['loc']}</loc>\n";
            $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
            $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$url['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        File::put("{$buildPath}/sitemap.xml", $xml);
    }

    /**
     * Build Keitaro inject file
     */
    private function buildKeitaroInject(Site $site, string $buildPath): void
    {
        $keitaroUrl = GlobalSetting::get('keitaro_url', '');
        $campaignId = GlobalSetting::get('keitaro_campaign_id', '');

        if (empty($keitaroUrl) || empty($campaignId)) {
            return;
        }

        $injectCode = <<<PHP
<?php
// Keitaro TDS Integration
\$keitaro_url = '{$keitaroUrl}';
\$campaign_id = '{$campaignId}';

if (!empty(\$_SERVER['HTTP_USER_AGENT'])) {
    \$ch = curl_init();
    curl_setopt(\$ch, CURLOPT_URL, \$keitaro_url . '/api.php');
    curl_setopt(\$ch, CURLOPT_POST, true);
    curl_setopt(\$ch, CURLOPT_POSTFIELDS, http_build_query([
        'campaign_id' => \$campaign_id,
        'visitor_code' => \$_COOKIE['_keitaro_visitor_code'] ?? '',
        'sub_id_1' => \$_GET['sub_id_1'] ?? '',
        'user_agent' => \$_SERVER['HTTP_USER_AGENT'],
        'ip' => \$_SERVER['REMOTE_ADDR'],
        'referrer' => \$_SERVER['HTTP_REFERER'] ?? '',
    ]));
    curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(\$ch, CURLOPT_TIMEOUT, 5);
    \$response = curl_exec(\$ch);
    curl_close(\$ch);
    
    if (\$response && \$data = json_decode(\$response, true)) {
        if (isset(\$data['visitor_code'])) {
            setcookie('_keitaro_visitor_code', \$data['visitor_code'], time() + 86400 * 365, '/');
        }
        if (!empty(\$data['body'])) {
            echo \$data['body'];
            exit;
        }
    }
}
PHP;

        File::put("{$buildPath}/keitaro.php", $injectCode);
    }

    /**
     * Get analytics codes for site
     */
    private function getAnalyticsCodes(Site $site): array
    {
        $codes = $site->analytics_codes ?? [];

        // Merge with global codes if not overridden
        $globalYM = GlobalSetting::get('global_yandex_metrika', '');
        $globalGA = GlobalSetting::get('global_google_analytics', '');
        $globalGTM = GlobalSetting::get('global_gtm', '');

        return [
            'yandex_metrika' => $codes['yandex_metrika'] ?? $globalYM,
            'google_analytics' => $codes['google_analytics'] ?? $globalGA,
            'google_tag_manager' => $codes['google_tag_manager'] ?? $globalGTM,
            'custom' => $codes['custom'] ?? GlobalSetting::get('global_custom_scripts', ''),
        ];
    }

    /**
     * Get backlinks HTML for site
     */
    private function getBacklinksHtml(Site $site): array
    {
        $backlinks = ['header' => '', 'footer' => '', 'content' => '', 'sidebar' => ''];

        foreach ($site->backlinks as $backlink) {
            $placement = $backlink->pivot->placement;
            $anchor = $backlink->pivot->anchor ?? $backlink->getRandomAnchor();
            $nofollow = $backlink->pivot->is_nofollow;

            if ($backlink->pivot->custom_html) {
                $html = $backlink->pivot->custom_html;
            } else {
                $html = $backlink->getHtmlLink($anchor, $nofollow);
            }

            $backlinks[$placement] .= $html . ' ';
        }

        return $backlinks;
    }

    /**
     * Generate Schema.org markup
     */
    private function generateSchemaMarkup(Site $site, string $pageName): string
    {
        $domain = $site->domain->domain;
        $baseUrl = "https://{$domain}";

        $schemas = [];

        // Organization/LocalBusiness
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $site->title,
            'url' => $baseUrl,
            'description' => $site->seo_description,
        ];

        // WebSite with SearchAction
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $site->title,
            'url' => $baseUrl,
        ];

        // WebPage
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $site->seo_title ?? $site->title,
            'description' => $site->seo_description,
            'url' => $pageName === 'home' ? $baseUrl : "{$baseUrl}/{$pageName}.html",
        ];

        // FAQ if exists
        $faqContent = $site->content['faq'] ?? null;
        if ($faqContent && !empty($faqContent['faq'])) {
            $faqItems = [];
            foreach ($faqContent['faq'] as $item) {
                $faqItems[] = [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['answer'],
                    ],
                ];
            }
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqItems,
            ];
        }

        // BreadcrumbList
        $breadcrumbItems = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Главная',
                'item' => $baseUrl,
            ],
        ];
        
        if ($pageName !== 'home') {
            $pageTitle = $site->content[$pageName]['title'] ?? ucfirst($pageName);
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $pageTitle,
                'item' => "{$baseUrl}/{$pageName}.html",
            ];
        }
        
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ];

        return '<script type="application/ld+json">' . json_encode($schemas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Generate schema for post
     */
    private function generatePostSchema(Post $post): string
    {
        $site = $post->site;
        $baseUrl = "https://{$site->domain->domain}";

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $post->type === 'news' ? 'NewsArticle' : 'BlogPosting',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'datePublished' => $post->published_at?->toW3cString(),
            'dateModified' => $post->updated_at->toW3cString(),
            'publisher' => [
                '@type' => 'Organization',
                'name' => $site->title,
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $post->url,
            ],
        ];

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Get build path for site
     */
    private function getBuildPath(Site $site): string
    {
        return "{$this->buildBasePath}/{$site->domain->domain}";
    }

    /**
     * Get post directory based on type
     */
    private function getPostDirectory(Post $post): string
    {
        return match ($post->type) {
            Post::TYPE_NEWS => 'news',
            Post::TYPE_FAQ => 'faq',
            default => 'blog',
        };
    }
}
