<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('url', 500);
            $table->string('email', 180)->nullable();
            $table->string('type', 20)->default('full'); // seo | security | full
            $table->string('status', 20)->default('pending'); // pending|running|completed|failed
            $table->unsignedTinyInteger('score_seo')->nullable();
            $table->unsignedTinyInteger('score_security')->nullable();
            $table->unsignedTinyInteger('score_total')->nullable();
            $table->json('results')->nullable();
            $table->text('error')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
