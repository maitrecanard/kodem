<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('type', 20)->default('premium');
            $table->string('status', 30)->default('pending_payment')->index();
            $table->string('name', 120);
            $table->string('email', 180)->index();
            $table->string('domain', 253)->index();
            $table->json('options')->nullable();
            $table->unsignedInteger('amount_cents')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('stripe_session_id', 255)->nullable()->index();
            $table->string('stripe_payment_intent', 255)->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('access_token', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_requests');
    }
};
