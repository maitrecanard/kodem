<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->unsignedInteger('pdf_price_cents')->default(900)->after('payment_reference');
            $table->timestamp('pdf_paid_at')->nullable()->after('pdf_price_cents');
            $table->unsignedInteger('cwv_price_cents')->default(1900)->after('pdf_paid_at');
            $table->timestamp('cwv_paid_at')->nullable()->after('cwv_price_cents');
            $table->json('cwv_results')->nullable()->after('cwv_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn(['pdf_price_cents', 'pdf_paid_at', 'cwv_price_cents', 'cwv_paid_at', 'cwv_results']);
        });
    }
};
