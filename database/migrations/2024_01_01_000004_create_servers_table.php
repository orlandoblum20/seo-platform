<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address', 45);
            $table->string('ssh_host')->nullable();
            $table->integer('ssh_port')->default(22);
            $table->string('ssh_user')->default('root');
            $table->text('ssh_key')->nullable(); // encrypted
            $table->text('ssh_password')->nullable(); // encrypted
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('max_domains')->nullable();
            $table->string('nginx_config_path')->default('/etc/nginx/sites-enabled');
            $table->string('sites_path')->default('/var/www/sites');
            $table->string('caddy_api_url')->nullable();
            $table->jsonb('settings')->nullable();
            $table->timestamp('last_health_check')->nullable();
            $table->enum('health_status', ['ok', 'warning', 'error', 'unknown'])->default('unknown');
            $table->timestamps();

            $table->index('is_active');
            $table->index('is_primary');
            $table->index('health_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
