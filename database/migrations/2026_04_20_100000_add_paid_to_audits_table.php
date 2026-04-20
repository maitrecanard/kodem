<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->unsignedInteger('price_cents')->default(2900)->after('score_total');
            $table->timestamp('paid_at')->nullable()->after('price_cents');
            $table->string('payment_reference', 64)->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn(['price_cents', 'paid_at', 'payment_reference']);
        });
    }
};
