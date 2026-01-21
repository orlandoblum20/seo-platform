<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('provider', ['anthropic', 'openai']);
            $table->string('name');
            $table->text('api_key'); // encrypted
            $table->string('api_endpoint')->nullable();
            $table->string('model');
            $table->integer('max_tokens')->default(4096);
            $table->float('temperature')->default(0.7);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('rate_limit')->nullable(); // requests per minute
            $table->integer('daily_limit')->nullable();
            $table->integer('requests_today')->default(0);
            $table->timestamp('last_request_at')->nullable();
            $table->jsonb('settings')->nullable();
            $table->timestamps();

            $table->index('provider');
            $table->index('is_active');
            $table->index('is_default');
            $table->index(['provider', 'is_default']);
        });

        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'float', 'boolean', 'array', 'json'])->default('string');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            $table->index('group');
            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_settings');
        Schema::dropIfExists('ai_settings');
    }
};
