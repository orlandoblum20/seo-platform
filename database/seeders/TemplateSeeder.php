<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Business Landing',
                'slug' => 'business',
                'type' => Template::TYPE_BUSINESS,
                'description' => 'Универсальный корпоративный лендинг для любого бизнеса',
                'is_active' => true,
                'sort_order' => 1,
                'structure' => [
                    'pages' => [
                        'home' => [
                            'sections' => ['hero', 'about', 'services', 'features', 'testimonials', 'faq', 'cta', 'contacts'],
                        ],
                    ],
                    'has_blog' => true,
                ],
                'default_prompts' => [
                    'hero' => [
                        'headline' => 'Создай мощный заголовок для {niche} компании, который привлекает внимание и содержит главное УТП. Максимум 10 слов.',
                        'subheadline' => 'Напиши подзаголовок, раскрывающий выгоды для клиента в сфере {niche}. 2-3 предложения.',
                        'cta_text' => 'Напиши текст кнопки призыва к действию для {niche}. 2-4 слова.',
                    ],
                    'about' => [
                        'title' => 'Напиши заголовок секции "О компании" для {niche}.',
                        'text' => 'Напиши текст о компании в сфере {niche}. 3-4 абзаца, описывающих опыт, ценности и подход к работе.',
                    ],
                    'services' => [
                        'items' => 'Создай список из 6 услуг для компании в сфере {niche}. Для каждой услуги: название, описание (2-3 предложения), список из 3-4 особенностей.',
                    ],
                    'features' => [
                        'items' => 'Создай 6 преимуществ работы с компанией в сфере {niche}. Для каждого: заголовок (3-5 слов) и описание (1-2 предложения).',
                    ],
                    'testimonials' => [
                        'items' => 'Создай 3 реалистичных отзыва клиентов о работе с компанией в сфере {niche}. Имена должны быть русскими.',
                    ],
                    'faq' => [
                        'items' => 'Создай 6 частых вопросов и ответов для компании в сфере {niche}. Ответы должны быть информативными, 2-4 предложения.',
                    ],
                ],
                'color_schemes' => [
                    [
                        'name' => 'Blue Professional',
                        'primary' => '#2563eb',
                        'primary_dark' => '#1d4ed8',
                        'secondary' => '#64748b',
                        'accent' => '#f59e0b',
                        'background' => '#ffffff',
                        'surface' => '#f8fafc',
                        'text' => '#1e293b',
                        'muted' => '#64748b',
                    ],
                    [
                        'name' => 'Green Nature',
                        'primary' => '#059669',
                        'primary_dark' => '#047857',
                        'secondary' => '#64748b',
                        'accent' => '#f59e0b',
                        'background' => '#ffffff',
                        'surface' => '#f0fdf4',
                        'text' => '#1e293b',
                        'muted' => '#64748b',
                    ],
                    [
                        'name' => 'Purple Creative',
                        'primary' => '#7c3aed',
                        'primary_dark' => '#6d28d9',
                        'secondary' => '#64748b',
                        'accent' => '#f59e0b',
                        'background' => '#ffffff',
                        'surface' => '#faf5ff',
                        'text' => '#1e293b',
                        'muted' => '#64748b',
                    ],
                    [
                        'name' => 'Red Energy',
                        'primary' => '#dc2626',
                        'primary_dark' => '#b91c1c',
                        'secondary' => '#64748b',
                        'accent' => '#fbbf24',
                        'background' => '#ffffff',
                        'surface' => '#fef2f2',
                        'text' => '#1e293b',
                        'muted' => '#64748b',
                    ],
                    [
                        'name' => 'Dark Premium',
                        'primary' => '#6366f1',
                        'primary_dark' => '#4f46e5',
                        'secondary' => '#94a3b8',
                        'accent' => '#fbbf24',
                        'background' => '#0f172a',
                        'surface' => '#1e293b',
                        'text' => '#f1f5f9',
                        'muted' => '#94a3b8',
                    ],
                ],
                'seo_settings' => [
                    'title_template' => '{title} | {niche} в {city}',
                    'description_template' => '{title} - профессиональные услуги в сфере {niche}. ✓ Опыт работы ✓ Гарантия качества ✓ Доступные цены. Звоните!',
                ],
            ],
            [
                'name' => 'Service Provider',
                'slug' => 'service',
                'type' => Template::TYPE_SERVICE,
                'description' => 'Шаблон для компаний, оказывающих услуги. С тарифами и процессом работы.',
                'is_active' => true,
                'sort_order' => 2,
                'structure' => [
                    'pages' => [
                        'home' => [
                            'sections' => ['hero', 'services', 'process', 'features', 'pricing', 'testimonials', 'faq', 'cta', 'contacts'],
                        ],
                    ],
                    'has_blog' => true,
                ],
                'default_prompts' => [
                    'hero' => [
                        'headline' => 'Создай заголовок для сервисной компании в сфере {niche}. Акцент на результат для клиента.',
                        'subheadline' => 'Напиши текст, объясняющий ценность услуг в сфере {niche}. 2-3 предложения с фокусом на выгоды.',
                    ],
                    'services' => [
                        'items' => 'Создай 6 услуг для сервисной компании в сфере {niche}. Название, описание, примерная цена.',
                    ],
                    'process' => [
                        'steps' => 'Опиши 4 этапа работы с клиентом для компании в сфере {niche}. Для каждого этапа: название и краткое описание.',
                    ],
                    'pricing' => [
                        'plans' => 'Создай 3 тарифных плана для услуг в сфере {niche}: базовый, стандарт, премиум. Цены, описание, список включённых услуг.',
                    ],
                ],
                'color_schemes' => [
                    [
                        'name' => 'Ocean Blue',
                        'primary' => '#0891b2',
                        'primary_dark' => '#0e7490',
                        'secondary' => '#64748b',
                        'accent' => '#f59e0b',
                        'background' => '#ffffff',
                        'surface' => '#f0fdfa',
                        'text' => '#1e293b',
                        'muted' => '#64748b',
                    ],
                    [
                        'name' => 'Warm Orange',
                        'primary' => '#ea580c',
                        'primary_dark' => '#c2410c',
                        'secondary' => '#64748b',
                        'accent' => '#2563eb',
                        'background' => '#ffffff',
                        'surface' => '#fff7ed',
                        'text' => '#1e293b',
                        'muted' => '#64748b',
                    ],
                ],
                'seo_settings' => [
                    'title_template' => '{title} - услуги {niche}',
                    'description_template' => 'Профессиональные услуги {niche} от {title}. ⭐ Выгодные цены ⭐ Быстрые сроки ⭐ Гарантия результата. Оставьте заявку!',
                ],
            ],
            [
                'name' => 'Landing Page',
                'slug' => 'landing',
                'type' => Template::TYPE_LANDING,
                'description' => 'Минималистичный одностраничный лендинг с фокусом на конверсию',
                'is_active' => true,
                'sort_order' => 3,
                'structure' => [
                    'pages' => [
                        'home' => [
                            'sections' => ['hero', 'features', 'about', 'cta', 'contacts'],
                        ],
                    ],
                    'has_blog' => false,
                ],
                'default_prompts' => [
                    'hero' => [
                        'headline' => 'Создай короткий, цепляющий заголовок для лендинга в сфере {niche}. Максимум 8 слов.',
                        'subheadline' => 'Одно предложение с главной выгодой для клиента в сфере {niche}.',
                    ],
                    'features' => [
                        'items' => 'Создай 4 ключевых преимущества для лендинга в сфере {niche}. Кратко и ёмко.',
                    ],
                ],
                'color_schemes' => [
                    [
                        'name' => 'Minimal Black',
                        'primary' => '#18181b',
                        'primary_dark' => '#09090b',
                        'secondary' => '#71717a',
                        'accent' => '#fbbf24',
                        'background' => '#ffffff',
                        'surface' => '#fafafa',
                        'text' => '#18181b',
                        'muted' => '#71717a',
                    ],
                ],
                'seo_settings' => [
                    'title_template' => '{title} | {niche}',
                    'description_template' => '{title} - {niche}. Узнайте больше и оставьте заявку на сайте!',
                ],
            ],
            [
                'name' => 'Corporate Website',
                'slug' => 'corporate',
                'type' => Template::TYPE_CORPORATE,
                'description' => 'Многостраничный корпоративный сайт: главная, о компании, услуги, блог, контакты',
                'is_active' => true,
                'sort_order' => 4,
                'structure' => [
                    'pages' => [
                        'home' => [
                            'sections' => ['hero', 'about_preview', 'services_preview', 'features', 'cta'],
                            'title' => 'Главная',
                        ],
                        'about' => [
                            'sections' => ['about_hero', 'history', 'team', 'values', 'cta'],
                            'title' => 'О компании',
                        ],
                        'services' => [
                            'sections' => ['services_hero', 'services_list', 'process', 'pricing', 'cta'],
                            'title' => 'Услуги',
                        ],
                        'contacts' => [
                            'sections' => ['contacts_hero', 'contacts_form', 'map', 'requisites'],
                            'title' => 'Контакты',
                        ],
                    ],
                    'has_blog' => true,
                    'is_multipage' => true,
                ],
                'default_prompts' => [
                    'hero' => [
                        'headline' => 'Создай корпоративный заголовок для компании в сфере {niche}. Профессиональный тон, 8-12 слов.',
                        'subheadline' => 'Напиши подзаголовок для корпоративного сайта в сфере {niche}. 2-3 предложения о миссии компании.',
                    ],
                    'about' => [
                        'title' => 'О компании',
                        'text' => 'Напиши развёрнутый текст о компании в сфере {niche}. История создания, миссия, ценности. 4-5 абзацев.',
                        'history' => 'Создай краткую историю компании в сфере {niche} с ключевыми датами и достижениями.',
                    ],
                    'team' => [
                        'title' => 'Наша команда',
                        'members' => 'Создай 4 профиля сотрудников компании в сфере {niche}: директор, менеджер, специалист, помощник. Имя, должность, краткое описание.',
                    ],
                    'services' => [
                        'items' => 'Создай 8 услуг для корпоративного сайта в сфере {niche}. Название, подробное описание (3-4 предложения), список преимуществ.',
                    ],
                    'contacts' => [
                        'title' => 'Контакты',
                        'address' => 'Сгенерируй реалистичный адрес офиса в Москве для компании в сфере {niche}.',
                        'working_hours' => 'Укажи стандартный график работы офиса.',
                    ],
                ],
                'color_schemes' => [
                    [
                        'name' => 'Corporate Blue',
                        'primary' => '#1e40af',
                        'primary_dark' => '#1e3a8a',
                        'secondary' => '#475569',
                        'accent' => '#eab308',
                        'background' => '#ffffff',
                        'surface' => '#f1f5f9',
                        'text' => '#0f172a',
                        'muted' => '#64748b',
                    ],
                    [
                        'name' => 'Corporate Gray',
                        'primary' => '#374151',
                        'primary_dark' => '#1f2937',
                        'secondary' => '#6b7280',
                        'accent' => '#3b82f6',
                        'background' => '#ffffff',
                        'surface' => '#f9fafb',
                        'text' => '#111827',
                        'muted' => '#6b7280',
                    ],
                    [
                        'name' => 'Corporate Green',
                        'primary' => '#166534',
                        'primary_dark' => '#14532d',
                        'secondary' => '#475569',
                        'accent' => '#fbbf24',
                        'background' => '#ffffff',
                        'surface' => '#f0fdf4',
                        'text' => '#0f172a',
                        'muted' => '#64748b',
                    ],
                ],
                'seo_settings' => [
                    'title_template' => '{title} - {page} | Официальный сайт',
                    'description_template' => '{title} - надёжная компания в сфере {niche}. ✓ Многолетний опыт ✓ Профессиональная команда ✓ Индивидуальный подход. {page}.',
                ],
            ],
        ];

        foreach ($templates as $templateData) {
            Template::updateOrCreate(
                ['slug' => $templateData['slug']],
                $templateData
            );
        }

        $this->command->info('Created ' . count($templates) . ' templates');
    }
}
