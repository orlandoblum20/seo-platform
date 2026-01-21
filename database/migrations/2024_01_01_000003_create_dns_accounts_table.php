<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('provider', ['cloudflare', 'dnspod']);
            $table->text('api_key'); // encrypted
            $table->text('api_secret')->nullable(); // encrypted, for DNSPOD
            $table->string('email')->nullable();
            $table->string('account_id')->nullable(); // Cloudflare account ID
            $table->boolean('is_active')->default(true);
            $table->jsonb('settings')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index('provider');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_accounts');
    }
};
