<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['landing', 'business', 'service', 'corporate', 'blog', 'catalog', 'shop', 'portfolio']);
            $table->text('description')->nullable();
            $table->string('preview_image')->nullable();
            $table->jsonb('structure'); // pages, sections, blocks
            $table->jsonb('default_prompts')->nullable(); // AI prompts for each section
            $table->jsonb('color_schemes')->nullable(); // Available color schemes
            $table->jsonb('seo_settings')->nullable(); // Default SEO settings
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
