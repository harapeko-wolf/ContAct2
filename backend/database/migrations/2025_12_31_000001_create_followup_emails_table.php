<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('followup_emails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('document_id');
            $table->string('viewer_ip', 45);
            $table->timestamp('triggered_at');
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['scheduled', 'sent', 'cancelled', 'failed'])->default('scheduled');
            $table->string('cancellation_reason')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // 外部キー制約
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');

            // インデックス
            $table->index(['company_id', 'scheduled_for'], 'idx_company_scheduled');
            $table->index(['status', 'scheduled_for'], 'idx_status_scheduled');
            $table->index(['company_id', 'document_id', 'viewer_ip'], 'idx_unique_followup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followup_emails');
    }
};
