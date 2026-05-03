<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('url', 500);
            $table->string('email', 180);
            $table->unsignedInteger('price_cents')->default(4900);
            $table->timestamp('active_until')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedTinyInteger('last_score_total')->nullable();
            $table->string('last_audit_uuid', 36)->nullable();
            $table->string('status', 20)->default('pending'); // pending|active|cancelled|expired
            $table->string('payment_reference', 64)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('active_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_subscriptions');
    }
};
