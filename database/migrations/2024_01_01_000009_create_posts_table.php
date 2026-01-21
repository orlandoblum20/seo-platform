<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['article', 'news', 'announcement', 'faq'])->default('article');
            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'generating', 'scheduled', 'published', 'error'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('generation_prompt')->nullable();
            $table->string('ai_provider')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
            $table->index('status');
            $table->index('type');
            $table->index('scheduled_at');
            $table->index('published_at');
            $table->index(['site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
