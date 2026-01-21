<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Template;

class PromptBuilder
{
    /**
     * Base system context for all prompts
     */
    private const BASE_CONTEXT = <<<EOT
You are an expert SEO copywriter and web content specialist. Your content should be:
- Engaging and professional
- Optimized for search engines while remaining natural
- Written in Russian language
- Free of AI-detectable patterns (avoid repetitive structures, generic phrases)
- Unique and valuable to readers
EOT;

    /**
     * Variations for prompt beginnings to avoid patterns
     */
    private array $promptVariations = [
        'create' => ['Создай', 'Напиши', 'Разработай', 'Подготовь', 'Составь'],
        'describe' => ['Опиши', 'Расскажи о', 'Представь', 'Изложи информацию о'],
        'quality' => ['качественный', 'профессиональный', 'привлекательный', 'убедительный', 'эффективный'],
    ];

    /**
     * Build SEO metadata prompt
     */
    public function buildSeoPrompt(string $niche, array $keywords, string $templateType): string
    {
        $keywordsList = implode(', ', array_slice($keywords, 0, 5));
        $variation = $this->getRandomVariation('create');
        $quality = $this->getRandomVariation('quality');

        return <<<EOT
{$variation} {$quality} SEO-метаданные для сайта в нише "{$niche}".

Ключевые слова для оптимизации: {$keywordsList}
Тип сайта: {$templateType}

Требования:
- Title: до 60 символов, включает главный ключ, привлекательный
- Description: до 160 символов, призыв к действию, ключевые слова естественно вписаны
- Keywords: 5-10 релевантных ключевых слов
- H1: уникальный заголовок, отличается от title, содержит ключ

Верни JSON в формате:
{
    "title": "SEO заголовок страницы",
    "description": "Мета-описание для поисковых систем",
    "keywords": ["ключ1", "ключ2", "ключ3"],
    "h1": "Главный заголовок страницы"
}
EOT;
    }

    /**
     * Build section content prompt
     */
    public function buildSectionPrompt(
        string $sectionName,
        array $sectionConfig,
        string $niche,
        array $keywords,
        ?string $customPrompt = null
    ): string {
        $keywordsList = implode(', ', array_slice($keywords, 0, 3));

        // Section-specific prompts
        $sectionPrompts = [
            'hero' => $this->buildHeroPrompt($niche, $keywordsList),
            'features' => $this->buildFeaturesPrompt($niche, $keywordsList),
            'about' => $this->buildAboutPrompt($niche, $keywordsList),
            'services' => $this->buildServicesPrompt($niche, $keywordsList),
            'testimonials' => $this->buildTestimonialsPrompt($niche),
            'cta' => $this->buildCtaPrompt($niche, $keywordsList),
            'contacts' => $this->buildContactsPrompt($niche),
            'pricing' => $this->buildPricingPrompt($niche),
        ];

        $basePrompt = $sectionPrompts[$sectionName] ?? $this->buildGenericSectionPrompt($sectionName, $niche, $keywordsList);

        if ($customPrompt) {
            $basePrompt .= "\n\nДополнительные инструкции: {$customPrompt}";
        }

        return $basePrompt;
    }

    /**
     * Hero section prompt
     */
    private function buildHeroPrompt(string $niche, string $keywords): string
    {
        $variation = $this->getRandomVariation('create');
        
        return <<<EOT
{$variation} контент для главного блока (hero section) сайта в нише "{$niche}".

Ключевые слова: {$keywords}

Требования:
- Headline: мощный заголовок, 5-10 слов, цепляет внимание
- Subheadline: поддерживающий текст, 15-25 слов, раскрывает ценность
- CTA: текст для кнопки призыва к действию, 2-4 слова
- Badge (опционально): короткий текст-бейдж ("Лидер рынка", "10 лет опыта")

Текст должен быть убедительным, создавать доверие и мотивировать к действию.

Верни JSON:
{
    "headline": "Главный заголовок",
    "subheadline": "Подзаголовок с описанием ценности",
    "cta_text": "Текст кнопки",
    "cta_secondary_text": "Текст второй кнопки (опционально)",
    "badge": "Текст бейджа"
}
EOT;
    }

    /**
     * Features section prompt
     */
    private function buildFeaturesPrompt(string $niche, string $keywords): string
    {
        $count = rand(4, 6);
        
        return <<<EOT
Создай контент для секции преимуществ/особенностей для бизнеса в нише "{$niche}".

Ключевые слова: {$keywords}

Создай {$count} уникальных преимуществ. Каждое должно:
- Иметь короткий заголовок (2-4 слова)
- Иметь описание (20-40 слов)
- Быть конкретным и измеримым где возможно
- Отличаться от других по смыслу

Также создай заголовок для всей секции.

Верни JSON:
{
    "section_title": "Заголовок секции",
    "section_subtitle": "Подзаголовок секции",
    "items": [
        {
            "title": "Название преимущества",
            "description": "Описание преимущества",
            "icon": "название иконки (shield, clock, star, users, chart, award)"
        }
    ]
}
EOT;
    }

    /**
     * About section prompt
     */
    private function buildAboutPrompt(string $niche, string $keywords): string
    {
        $variation = $this->getRandomVariation('describe');
        
        return <<<EOT
{$variation} компанию в нише "{$niche}" для секции "О нас".

Ключевые слова для SEO: {$keywords}

Создай:
- Заголовок секции
- Основной текст (100-150 слов): история, миссия, ценности
- 3-4 ключевых факта о компании (в цифрах)
- Короткий слоган или девиз

Текст должен вызывать доверие, быть профессиональным но не сухим.

Верни JSON:
{
    "title": "Заголовок секции",
    "subtitle": "Подзаголовок",
    "text": "Основной текст о компании...",
    "stats": [
        {"value": "10+", "label": "лет на рынке"},
        {"value": "500+", "label": "довольных клиентов"}
    ],
    "slogan": "Слоган компании"
}
EOT;
    }

    /**
     * Services section prompt
     */
    private function buildServicesPrompt(string $niche, string $keywords): string
    {
        $count = rand(4, 6);
        
        return <<<EOT
Создай описание {$count} услуг для компании в нише "{$niche}".

Ключевые слова: {$keywords}

Для каждой услуги:
- Название (2-5 слов)
- Краткое описание (30-50 слов)
- 3-4 ключевых пункта что входит
- Призыв к действию

Верни JSON:
{
    "section_title": "Заголовок секции услуг",
    "section_subtitle": "Подзаголовок",
    "items": [
        {
            "title": "Название услуги",
            "description": "Описание услуги",
            "features": ["пункт 1", "пункт 2", "пункт 3"],
            "cta_text": "Текст кнопки"
        }
    ]
}
EOT;
    }

    /**
     * Testimonials section prompt
     */
    private function buildTestimonialsPrompt(string $niche): string
    {
        $count = rand(3, 4);
        
        return <<<EOT
Создай {$count} реалистичных отзыва клиентов для компании в нише "{$niche}".

Каждый отзыв должен:
- Быть от лица реального человека (придумай имя и должность)
- Содержать конкретику (результаты, сроки, впечатления)
- Звучать естественно, не рекламно
- Быть разной длины (50-100 слов)
- Иметь разный тон (деловой, эмоциональный, сдержанный)

Верни JSON:
{
    "section_title": "Заголовок секции отзывов",
    "items": [
        {
            "name": "Имя Фамилия",
            "position": "Должность, Компания",
            "text": "Текст отзыва...",
            "rating": 5
        }
    ]
}
EOT;
    }

    /**
     * CTA section prompt
     */
    private function buildCtaPrompt(string $niche, string $keywords): string
    {
        return <<<EOT
Создай контент для секции призыва к действию (CTA) для бизнеса в нише "{$niche}".

Ключевые слова: {$keywords}

Нужно:
- Сильный заголовок, побуждающий к действию
- Краткий убедительный текст (20-30 слов)
- Текст для основной кнопки
- Текст для дополнительной ссылки (опционально)

Верни JSON:
{
    "title": "Призывающий заголовок",
    "text": "Убедительный текст",
    "button_text": "Текст кнопки",
    "secondary_link_text": "Или узнайте больше"
}
EOT;
    }

    /**
     * Contacts section prompt
     */
    private function buildContactsPrompt(string $niche): string
    {
        return <<<EOT
Создай контент для секции контактов компании в нише "{$niche}".

Нужно:
- Заголовок секции
- Приглашающий текст (20-30 слов)
- Текст для формы обратной связи
- Placeholder тексты для полей формы

Верни JSON:
{
    "title": "Заголовок секции",
    "subtitle": "Приглашающий текст",
    "form_title": "Заголовок формы",
    "form_subtitle": "Подзаголовок формы",
    "placeholders": {
        "name": "Ваше имя",
        "email": "Email",
        "phone": "Телефон",
        "message": "Сообщение"
    },
    "submit_text": "Текст кнопки отправки",
    "success_message": "Сообщение об успешной отправке"
}
EOT;
    }

    /**
     * Pricing section prompt
     */
    private function buildPricingPrompt(string $niche): string
    {
        return <<<EOT
Создай контент для секции цен/тарифов для бизнеса в нише "{$niche}".

Создай 3 тарифных плана (базовый, стандартный, премиум).

Для каждого:
- Название тарифа
- Цена (можно "от X руб." или "по запросу")
- Период (месяц, проект, разово)
- 5-7 пунктов что включено
- Пометка если рекомендуемый

Верни JSON:
{
    "section_title": "Заголовок секции",
    "section_subtitle": "Подзаголовок",
    "plans": [
        {
            "name": "Название тарифа",
            "price": "10 000",
            "period": "месяц",
            "features": ["пункт 1", "пункт 2"],
            "is_popular": false,
            "cta_text": "Выбрать"
        }
    ]
}
EOT;
    }

    /**
     * Generic section prompt
     */
    private function buildGenericSectionPrompt(string $sectionName, string $niche, string $keywords): string
    {
        return <<<EOT
Создай контент для секции "{$sectionName}" сайта в нише "{$niche}".

Ключевые слова: {$keywords}

Создай подходящий контент с заголовком и текстом.

Верни JSON:
{
    "title": "Заголовок секции",
    "subtitle": "Подзаголовок",
    "text": "Основной текст секции"
}
EOT;
    }

    /**
     * Build FAQ prompt
     */
    public function buildFaqPrompt(string $niche, array $keywords, int $count = 6): string
    {
        $keywordsList = implode(', ', array_slice($keywords, 0, 5));

        return <<<EOT
Создай {$count} часто задаваемых вопросов (FAQ) для бизнеса в нише "{$niche}".

Ключевые слова для SEO: {$keywordsList}

Требования к вопросам:
- Реалистичные вопросы, которые задают клиенты
- Включают ключевые слова естественно
- Разнообразные темы (услуги, цены, сроки, гарантии, процесс работы)

Требования к ответам:
- Полные и информативные (50-100 слов)
- Полезные для пользователя
- Включают ключевые слова

Верни JSON:
{
    "section_title": "Часто задаваемые вопросы",
    "faq": [
        {
            "question": "Текст вопроса?",
            "answer": "Текст ответа..."
        }
    ]
}
EOT;
    }

    /**
     * Build blog post prompt
     */
    public function buildBlogPostPrompt(string $niche, array $keywords, string $type = 'article'): string
    {
        $keywordsList = implode(', ', array_slice($keywords, 0, 3));
        $mainKeyword = $keywords[0] ?? $niche;

        $typeInstructions = match ($type) {
            'news' => 'Напиши новость актуальную для индустрии. Стиль: информационный, краткий.',
            'announcement' => 'Напиши анонс/объявление. Стиль: официальный, краткий (200-300 слов).',
            'faq' => 'Напиши расширенный FAQ-пост с 5-7 вопросами и подробными ответами.',
            default => 'Напиши информативную статью. Стиль: экспертный, полезный для читателя.',
        };

        return <<<EOT
{$typeInstructions}

Тематика: {$niche}
Главный ключ для SEO: {$mainKeyword}
Дополнительные ключи: {$keywordsList}

Требования:
- Уникальный, полезный контент
- SEO-оптимизированный заголовок
- Подзаголовки (H2, H3) с ключевыми словами
- Длина: 500-800 слов для статьи, 200-300 для новости/анонса
- Естественное вписывание ключевых слов

Верни JSON:
{
    "title": "Заголовок статьи",
    "slug": "url-slug-stati",
    "excerpt": "Краткое описание для превью (150-200 символов)",
    "content": "Полный HTML-контент статьи с подзаголовками <h2>, <h3>, параграфами <p>",
    "seo_title": "SEO заголовок (до 60 символов)",
    "seo_description": "SEO описание (до 160 символов)"
}
EOT;
    }

    /**
     * Get random variation
     */
    private function getRandomVariation(string $type): string
    {
        $variations = $this->promptVariations[$type] ?? [''];
        return $variations[array_rand($variations)];
    }
}
