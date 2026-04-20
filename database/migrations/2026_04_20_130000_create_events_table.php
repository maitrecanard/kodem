<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 60)->index();   // ex. button_click, audit.submitted
            $table->string('name', 80)->index();   // ex. hero_cta_audit, audit_XXX
            $table->string('url', 255)->nullable();
            $table->string('referer', 255)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('session_hash', 64)->nullable()->index();
            $table->string('user_agent', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
