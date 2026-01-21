<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autopost_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->unique()->constrained()->onDelete('cascade');
            $table->boolean('is_enabled')->default(false);
            $table->enum('frequency', [
                'daily', 
                'every_2_days', 
                'every_3_days', 
                'weekly', 
                'biweekly', 
                'random'
            ])->default('every_3_days');
            $table->integer('frequency_variance')->default(0); // days +/- for randomization
            $table->jsonb('post_types')->nullable(); // ['article', 'news']
            $table->time('time_range_start')->default('09:00');
            $table->time('time_range_end')->default('18:00');
            $table->boolean('weekdays_only')->default(false);
            $table->jsonb('custom_prompts')->nullable();
            $table->timestamp('last_post_at')->nullable();
            $table->timestamp('next_post_at')->nullable();
            $table->integer('posts_count')->default(0);
            $table->integer('errors_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index('is_enabled');
            $table->index('next_post_at');
            $table->index(['is_enabled', 'next_post_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autopost_settings');
    }
};
