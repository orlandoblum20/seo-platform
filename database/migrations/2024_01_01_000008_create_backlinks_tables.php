<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backlinks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->json('anchors')->nullable();
            $table->string('group')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index('group');
            $table->index('is_active');
        });

        Schema::create('site_backlinks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('backlink_id')->constrained()->onDelete('cascade');
            $table->string('anchor')->nullable();
            $table->enum('placement', ['header', 'footer', 'content', 'sidebar'])->default('footer');
            $table->boolean('is_nofollow')->default(false);
            $table->text('custom_html')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'backlink_id']);
            $table->index('placement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_backlinks');
        Schema::dropIfExists('backlinks');
    }
};
