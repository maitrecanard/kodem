<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->string('email', 180);
            $table->string('reason', 40);
            $table->unsignedTinyInteger('score_at_send');
            $table->string('subject', 200);
            $table->string('status', 20)->default('sent');
            $table->text('error')->nullable();
            $table->string('message_id', 200)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();

            $table->index(['audit_id', 'sent_at']);
            $table->index(['reason', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_followups');
    }
};
