<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->constrained()->onDelete('restrict');
            $table->enum('status', [
                'draft',
                'generating',
                'generated',
                'publishing',
                'published',
                'unpublished',
                'error'
            ])->default('draft');
            $table->string('title');
            $table->string('niche')->nullable();
            $table->jsonb('keywords')->nullable(); // array of keywords
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->text('seo_keywords')->nullable();
            $table->jsonb('content')->nullable(); // Generated content for all sections
            $table->jsonb('settings')->nullable(); // Site-specific settings
            $table->jsonb('color_scheme')->nullable(); // Selected colors
            $table->jsonb('analytics_codes')->nullable(); // Metrika, GA, etc.
            $table->text('custom_css')->nullable();
            $table->text('custom_js')->nullable();
            $table->text('custom_head')->nullable(); // Custom HTML in <head>
            $table->boolean('keitaro_enabled')->default(true);
            $table->timestamp('generation_started_at')->nullable();
            $table->timestamp('generation_completed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('unpublished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('template_id');
            $table->index('published_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
