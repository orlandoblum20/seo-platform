<?php

namespace Database\Seeders;

use App\Models\GlobalSetting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Content Generation
            ['key' => 'content_humanization_level', 'value' => 2, 'type' => 'integer', 'group' => 'content'],
            ['key' => 'default_content_length', 'value' => 'medium', 'type' => 'string', 'group' => 'content'],
            ['key' => 'include_faq_schema', 'value' => true, 'type' => 'boolean', 'group' => 'content'],
            ['key' => 'include_organization_schema', 'value' => true, 'type' => 'boolean', 'group' => 'content'],
            
            // SEO Defaults
            ['key' => 'default_robots_txt', 'value' => "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /api/", 'type' => 'string', 'group' => 'seo'],
            ['key' => 'auto_generate_sitemap', 'value' => true, 'type' => 'boolean', 'group' => 'seo'],
            ['key' => 'sitemap_include_images', 'value' => false, 'type' => 'boolean', 'group' => 'seo'],
            
            // Autopost
            ['key' => 'autopost_enabled', 'value' => true, 'type' => 'boolean', 'group' => 'autopost'],
            ['key' => 'autopost_default_frequency', 'value' => 'every_3_days', 'type' => 'string', 'group' => 'autopost'],
            ['key' => 'autopost_time_start', 'value' => '09:00', 'type' => 'string', 'group' => 'autopost'],
            ['key' => 'autopost_time_end', 'value' => '18:00', 'type' => 'string', 'group' => 'autopost'],
            ['key' => 'autopost_weekdays_only', 'value' => false, 'type' => 'boolean', 'group' => 'autopost'],
            
            // Keitaro
            ['key' => 'keitaro_enabled', 'value' => false, 'type' => 'boolean', 'group' => 'keitaro'],
            ['key' => 'keitaro_url', 'value' => '', 'type' => 'string', 'group' => 'keitaro'],
            ['key' => 'keitaro_campaign_id', 'value' => '', 'type' => 'string', 'group' => 'keitaro'],
            ['key' => 'keitaro_excluded_domains', 'value' => [], 'type' => 'array', 'group' => 'keitaro'],
            
            // Analytics
            ['key' => 'global_yandex_metrika', 'value' => '', 'type' => 'string', 'group' => 'analytics'],
            ['key' => 'global_google_analytics', 'value' => '', 'type' => 'string', 'group' => 'analytics'],
            ['key' => 'global_gtm', 'value' => '', 'type' => 'string', 'group' => 'analytics'],
            ['key' => 'global_custom_scripts', 'value' => '', 'type' => 'string', 'group' => 'analytics'],
            
            // System
            ['key' => 'sites_per_page', 'value' => 25, 'type' => 'integer', 'group' => 'system'],
            ['key' => 'domains_per_page', 'value' => 50, 'type' => 'integer', 'group' => 'system'],
            ['key' => 'enable_activity_log', 'value' => true, 'type' => 'boolean', 'group' => 'system'],
            ['key' => 'activity_log_retention_days', 'value' => 30, 'type' => 'integer', 'group' => 'system'],
        ];

        foreach ($settings as $setting) {
            GlobalSetting::set(
                $setting['key'],
                $setting['value'],
                $setting['type'],
                $setting['group']
            );
        }

        $this->command->info('Created ' . count($settings) . ' default settings');
    }
}
