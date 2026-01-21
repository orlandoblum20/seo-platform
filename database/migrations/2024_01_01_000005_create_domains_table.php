<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->foreignId('dns_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', [
                'pending', 
                'dns_configuring', 
                'ssl_pending', 
                'active', 
                'error', 
                'suspended'
            ])->default('pending');
            $table->enum('ssl_status', ['none', 'pending', 'active', 'error'])->default('none');
            $table->string('cloudflare_zone_id')->nullable();
            $table->string('dnspod_domain_id')->nullable();
            $table->jsonb('nameservers')->nullable(); // NS records assigned by DNS provider
            $table->integer('dr_rating')->nullable();
            $table->integer('iks_rating')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->string('registrar')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('settings')->nullable();
            $table->timestamp('last_check_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('ssl_status');
            $table->index('dns_account_id');
            $table->index('server_id');
            $table->index('dr_rating');
            $table->index('iks_rating');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
