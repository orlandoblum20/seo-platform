<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->enum('status', ['success', 'failed', 'blocked', '2fa_pending', '2fa_failed']);
            $table->string('failure_reason')->nullable();
            $table->timestamp('logged_in_at');

            $table->index(['user_id', 'logged_in_at']);
            $table->index(['ip_address', 'logged_in_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_history');
    }
};
